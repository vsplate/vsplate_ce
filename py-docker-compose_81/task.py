#coding:utf-8
# http://www.cnblogs.com/taceywong/p/5843944.html
# https://blog.csdn.net/vinsuan1993/article/details/78158589

import json, re, os, hashlib, shutil, tempfile, datetime, stat, base64, random, string, sys
import signal, time, threading, inspect, ctypes, logging
import urllib2
import redis
import config

cache_dir = '/tmp/vs_cache'
data_dir = '/py-docker-compose/data';
debug = config.debug

queue_redis_host = config.queue_redis_host
queue_redis_pwd = config.queue_redis_pwd

prodcons_queue = 'task:prodcons:queue'

def debug_info(info):
	if debug:
		print info

def _async_raise(tid, exctype):
    """raises the exception, performs cleanup if needed"""
    tid = ctypes.c_long(tid)
    if not inspect.isclass(exctype):
        exctype = type(exctype)
    res = ctypes.pythonapi.PyThreadState_SetAsyncExc(tid, ctypes.py_object(exctype))
    if res == 0:
        raise ValueError("invalid thread id")
    elif res != 1:
        # """if it returns a number greater than one, you're in trouble,
        # and you should call it again with exc=NULL to revert the effect"""
        ctypes.pythonapi.PyThreadState_SetAsyncExc(tid, None)
        raise SystemError("PyThreadState_SetAsyncExc failed")
 
def stop_thread(thread):
    _async_raise(thread.ident, SystemExit)

#获取压缩包下载地址
def cache_archive_project(addr):
	addr = addr.strip()
	logging.info( "ARCHIVE DOWN: "+addr )
	debug_info("ARCHIVE DOWN: "+addr)
	if not is_valid_url(addr):
		debug_info("Invalid archive url: "+addr)
		return False
	if not os.path.splitext(addr)[1] == '.zip':
		debug_info("Invalid zip file")
		return False
	#获取项目缓存目录,存放压缩包和解压文件
	addr_cache_dir = cache_dir+"/archive_"+md5_hash(addr)
	#获取压缩包下载地址
	codedown_url = addr
	#压缩包保存地址
	filename = addr_cache_dir+'/'+md5_hash(codedown_url)+".zip"
	#压缩包解压后地址
	repo_cache_dir = addr_cache_dir+'/'+md5_hash(filename)
	#清空目录
	exec_cmd("rm -rf "+addr_cache_dir+"; mkdir "+addr_cache_dir);
	#创建项目缓存目录
	#if not os.path.isdir(addr_cache_dir):
	#	os.mkdir(addr_cache_dir)
	logging.info( 'addr_cache_dir: '+addr_cache_dir )
	debug_info('addr_cache_dir: '+addr_cache_dir)
	
	# 下载项目压缩包
	if not os.path.isfile(filename):
		cmd = 'sleep 3;wget --max-redirect 5 "'+codedown_url+'" -O '+filename
		exec_cmd(cmd)
	# 清空解压保存目录
	exec_cmd("rm -rf "+repo_cache_dir+" ; mkdir "+repo_cache_dir + "; chmod -R 777 "+repo_cache_dir)
	logging.info( 'repo_cache_dir: '+repo_cache_dir )
	debug_info('repo_cache_dir: '+repo_cache_dir)
	#解压repo到保存目录
	cmd = 'test -f "'+filename+'" && unzip '+filename+' -d '+repo_cache_dir+' | rm -rf '+repo_cache_dir
	exec_cmd(cmd)
	
	# 解压保存目录不存在，表示重命名失败，删除全部文件
	if not os.path.isdir(repo_cache_dir):
		debug_info("Remove addr_cache_dir")
		shutil.rmtree(addr_cache_dir)
		return False
	os.chmod(repo_cache_dir, stat.S_IRWXU|stat.S_IRWXG|stat.S_IRWXO)
	return repo_cache_dir

def exec_cmd(cmd):
	logging.info( cmd )
	return os.popen(cmd).readlines()

def md5_hash(str):
	m2 = hashlib.md5()   
	m2.update(str)   
	return m2.hexdigest()   

def resp_json(status=0, msg='', data=''):
	logging.info( msg )
	debug_info("resp: "+msg)
	return json.dumps({'status':status, 'msg':msg, 'data':data})

def is_json(data):
	try:
		json_object = json.loads(data)
	except ValueError, e:
		return False
	return True

def get_json_data_param(json_data, name, default=None):
	if json_data.has_key(name):
		data = json_data[name]
		if data == '':
			data = None
		return data
	return default

def is_validstr(str):
	if str == None:
		return False
	p = re.compile('^[0-9a-zA-Z_\.\-:]+$')
	if p.match(str):
		return True
	else:
		return False

def is_valid_url(str):
	if str == None:
		return False
	p = re.compile('^(https|http)?:/{2}\w.+$')
	if p.match(str):
		return True
	else:
		return False

def is_filestr(str):
	if str == None:
		return False
	p = re.compile('^[0-9a-zA-Z_\/\.@\-:]+$')
	if p.match(str):
		return True
	else:
		return False
		

def task_index():
	if not os.system('/etc/init.d/docker status') == 0:
		return 'Docker service is down.'
	return 'Docker service is running'

def get_project_dir(project):
	if not is_validstr(project):
		return False
	return data_dir+"/"+project

def get_yml_file(project):
	if not is_validstr(project):
		return False
	return data_dir+"/"+project+"/docker-compose.yml"
	
def task_archive_up(data):
	logging.info( data )
	if not is_json(data):
		return resp_json(0, 'Invalid Data')

	params = json.loads(data)
	
	action = get_json_data_param(params, 'action')
	
	if action != 'archive-up' :
		return resp_json(0, 'invalid action')
	
	json_data = get_json_data_param(params, 'data')
	
	project = get_json_data_param(json_data, 'project')
	archive_url = get_json_data_param(json_data, 'archive_url')
	docker_compose = get_json_data_param(json_data, 'docker_compose')
	if not is_validstr(project):
		logging.info( "project: "+project )
		return resp_json(0, 'invalid id or invalid name')
	archive_cache_dir = cache_archive_project(archive_url)
	if not archive_cache_dir:
		return resp_json(0, 'download archive failed')
	
	project_dir = get_project_dir(project)
	yml_file = get_yml_file(project)
	try:
		docker_compose = base64.b64decode(docker_compose)
	except:
		docker_compose = ''
	
	exec_cmd("rm -rf "+project_dir+"; mkdir "+project_dir);
	logging.info( 'project_dir: '+project_dir )
	
	addr_cache_dir = cache_dir+"/archive_"+md5_hash(archive_url)
	exec_cmd('cp -R '+archive_cache_dir+'/* '+project_dir+' && chmod -R 777 '+project_dir+" && rm -rf "+addr_cache_dir)
	
	# 重写 docker-compose.yml
	try:
		fo = open(yml_file, "w")
		fo.write(docker_compose)
	finally:
		if fo:
			fo.close()
	
	# 检查 docker-compose.yml 是否写成功
	try:
		f=open(yml_file, 'r')
		c = f.read()
	finally:
		if f:
			f.close()
	if c != docker_compose:
		shutil.rmtree(project_dir)
		return resp_json(0, 'over write docker-compose.yml failed')

	result = exec_cmd('docker-compose -f '+yml_file+' -p '+project+' up -d > '+project_dir+'/docker-compose.log; echo date +%s > '+project_dir+'/vs_done.txt')
	return resp_json(1,'',{'project':project,'result':result})

def task_action(i, data):
	if not is_json(data):
		return resp_json(0, 'Invalid Data')
	params = json.loads(data)
	
	action = get_json_data_param(params, 'action')
	print "new task: "+action
	if action == 'archive-up':
		result = task_archive_up(data)
	else:
		return resp_json(0, 'Invalid task action')
	
	result_json = json.loads(result)
	if get_json_data_param(result_json, 'status') != 1 and get_json_data_param(result_json, 'status') != '1' :
		return resp_json(0, 'up failed')
	return True

def timeout_action(tHandle,timeout):
	tHandle.setDaemon(True)
	tHandle.start()
	tHandle.join(timeout)

def action_test(tno, strr):
	aid = ''.join(random.choice(string.ascii_letters) for m in range(8))
	timeout = random.randint(1, 20)
	logging.info( str(tno)+" Task "+aid+" get "+strr )
        time.sleep(timeout)
        logging.info( str(tno)+" Task "+aid+" done "+strr )

class Task(object):
	def __init__(self):
		self.thread_num = 10
		self.threads = []
		self.thread_timeout = 300 # seconds
		self.rcon = redis.StrictRedis(host=queue_redis_host, db=5, password=queue_redis_pwd)
		self.queue = prodcons_queue
		self.__init_thread_pool(self.thread_num)

	def __init_thread_pool(self, thread_num):
		for i in range(thread_num):
			self.threads.append({'time':0,"thread":threading.Thread()})

	def listen_task(self):
		while True:
			for i in range(self.thread_num):
				# 线程超时
				if self.threads[i]['thread'].isAlive() and int(time.time()) - self.threads[i]['time'] > 300:
					stop_thread(self.threads[i]['thread'])
				# 如果线程已结束，加入新进程
				if not self.threads[i]['thread'].isAlive():
					data = self.rcon.blpop(self.queue, 0)[1]
					self.threads[i]['thread'] = threading.Thread(target=task_action,args=(i, data,))
					self.threads[i]['time'] = int(time.time())
					self.threads[i]['thread'].start()

if __name__ == '__main__':
	#缓存目录不存在，则创建缓存目录
	if not os.path.isdir(cache_dir):
		os.mkdir(cache_dir)
	#仍然不存在，表示创建失败
	if not os.path.isdir(cache_dir):
		print 'Make cache_dir failed'
		sys.exit()
	#数据目录不存在，则创建数据目录
	if not os.path.isdir(data_dir):
		os.mkdir(data_dir)
	#仍然不存在，表示创建失败
	if not os.path.isdir(data_dir):
		print 'Make usr_files_dir failed'
		sys.exit()
	print "waiting..."
	logging.info( '监听任务队列' )
	Task().listen_task()
		
	
