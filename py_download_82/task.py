#coding:utf-8

import json, re, os, hashlib, shutil, tempfile, datetime, stat, base64, random, string, sys
import signal, time, threading, inspect, ctypes, logging
import urllib2
import redis
import config

cache_dir = config.cache_dir
usr_files_dir = config.usr_files_dir

debug = config.debug

queue_redis_host = config.queue_redis_host
queue_redis_pwd = config.queue_redis_pwd

github_client_id = config.github_client_id
github_client_secret = config.github_client_secret

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

# 获取github项目名
def get_git_proj_repo(addr):
	addr = addr.replace('https://github.com/', '')
	return addr.strip('/')

#获取git项目的缓存下载地址
def cache_git_project(addr, github_updated_at):
	addr = addr.strip()
	logging.info( "GIT DOWN: "+addr )
	debug_info("GIT DOWN: "+addr)
	if not is_valid_github_project(addr):
		debug_info("Invalid github project: "+addr)
		return False
	#获取项目缓存目录,存放压缩包和解压文件
	addr_cache_dir = cache_dir+"/github_"+md5_hash(addr)
	#获取压缩包下载地址
	codedown_url = 'https://api.github.com/repos/'+get_git_proj_repo(addr)+'/tarball?client_id='+github_client_id+'&client_secret='+github_client_secret
	#压缩包保存地址
	filename = addr_cache_dir+'/'+md5_hash(github_updated_at)+md5_hash(codedown_url)+".tar.gz"
	#压缩包解压后地址
	repo_cache_dir = addr_cache_dir+'/'+md5_hash(filename)
	#创建项目缓存目录
	if not os.path.isdir(addr_cache_dir):
		os.mkdir(addr_cache_dir)
	logging.info( 'addr_cache_dir: '+addr_cache_dir )
	debug_info('addr_cache_dir: '+addr_cache_dir)
	# 项目已存在，直接返回项目目录
	if os.path.isfile(filename) and os.path.isdir(repo_cache_dir):
		#shutil.rmtree(repo_cache_dir)
		debug_info("Cache exists: "+repo_cache_dir)
		return repo_cache_dir
	# 如果压缩包不存在，下载项目压缩包
	if not os.path.isfile(filename):
		cmd = 'wget --max-redirect 5 "'+codedown_url+'" -O '+filename
		exec_cmd(cmd)
	# 删除解压保存目录，不然下面无法重命名
	if os.path.isdir(repo_cache_dir):
		shutil.rmtree(repo_cache_dir)
	
	#解压repo到缓存目录
	cmd = 'test -f "'+filename+'" && tar -xf '+filename+' -C '+addr_cache_dir
	exec_cmd(cmd)
	#将文件目录重命名为解压保存目录
	cmd = 'dirname=`tar -tzf '+filename+' | head -1 | cut -f1 -d"/"`;test -d '+addr_cache_dir+'/$dirname && mv '+addr_cache_dir+'/$dirname '+repo_cache_dir+';'
	exec_cmd(cmd)
	
	# 解压保存目录不存在，表示重命名失败，删除全部文件
	if not os.path.isdir(repo_cache_dir):
		debug_info("Remove addr_cache_dir")
		shutil.rmtree(addr_cache_dir)
		return False
	os.chmod(repo_cache_dir, stat.S_IRWXU|stat.S_IRWXG|stat.S_IRWXO)
	return repo_cache_dir

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
	#创建项目缓存目录
	if not os.path.isdir(addr_cache_dir):
		os.mkdir(addr_cache_dir)
	logging.info( 'addr_cache_dir: '+addr_cache_dir )
	debug_info('addr_cache_dir: '+addr_cache_dir)
	
	# 下载项目压缩包
	if not os.path.isfile(filename):
		cmd = 'wget --max-redirect 5 "'+codedown_url+'" -O '+filename
		exec_cmd(cmd)
	# 清空解压保存目录
	if os.path.isdir(repo_cache_dir):
		shutil.rmtree(repo_cache_dir)
	if not os.path.isdir(repo_cache_dir):
		os.mkdir(repo_cache_dir)
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
	logging.info( "CMD: "+cmd )
	debug_info("CMD: "+cmd)
	return os.popen(cmd).readlines()

def md5_hash(str):
	m2 = hashlib.md5()   
	m2.update(str)   
	return m2.hexdigest()   

def resp_json(status=0, msg='', data=''):
	logging.info( msg )
	if debug:
		print status, msg, data
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

def is_valid_github_project(str):
	if str == None:
		return False
	p = re.compile('^https://github.com/[0-9a-zA-Z_\.\-:]+/[0-9a-zA-Z_\.\-:]+')
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
	
def task_github_download(data):
	logging.info( data )
	if not is_json(data):
		return resp_json(0, 'Invalid Data')

	params = json.loads(data)
	
	action = get_json_data_param(params, 'action')
	
	if action != 'github-download' :
		return resp_json(0, 'invalid action')
	
	json_data = get_json_data_param(params, 'data')
	
	savedir = get_json_data_param(json_data, 'dir')
	github_url = get_json_data_param(json_data, 'github_url')
	docker_compose = get_json_data_param(json_data, 'docker_compose')
	github_updated_at = get_json_data_param(json_data, 'github_updated_at')
	
	# 下载github项目
	github_cache_dir = cache_git_project(github_url, github_updated_at)
	if not github_cache_dir:
		return resp_json(0, 'download github project failed')
	
	if not is_validstr(savedir):
		return False
	savedir = usr_files_dir+'/'+savedir
	yml_file = savedir+'/docker-compose.yml'
	try:
		docker_compose = base64.b64decode(docker_compose)
	except:
		docker_compose = ''
	
	# 如果目录已存在则清空
	if os.path.isdir(savedir):
		shutil.rmtree(savedir)
	if not os.path.isdir(savedir):
		os.mkdir(savedir)
	logging.info( 'savedir: '+savedir )
	
	exec_cmd('cp -R '+github_cache_dir+'/* '+savedir)
	exec_cmd('chmod -R 777 '+savedir)
	
	# 删除创建锁
	lock_file = savedir+'/creating.lock'
	if os.path.exists(lock_file):
		os.remove(lock_file)
		
	if docker_compose != '':
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
			shutil.rmtree(savedir)
			return resp_json(0, 'over write docker-compose.yml failed')
	
	return resp_json(1,'',{'savedir':savedir})


def task_archive_download(data):
	logging.info( data )
	if not is_json(data):
		return resp_json(0, 'Invalid Data')

	params = json.loads(data)
	
	action = get_json_data_param(params, 'action')
	
	if action != 'archive-download' :
		return resp_json(0, 'invalid action')
	
	json_data = get_json_data_param(params, 'data')
	
	savedir = get_json_data_param(json_data, 'dir')
	archive_url = get_json_data_param(json_data, 'archive_url')
	docker_compose = get_json_data_param(json_data, 'docker_compose')
	if not is_validstr(savedir):
		logging.info( "savedir: "+savedir )
		debug_info("savedir: "+savedir);
		return resp_json(0, 'invalid savedir')
		
	# 下载压缩包
	archive_cache_dir = cache_archive_project(archive_url)
	if not archive_cache_dir:
		return resp_json(0, 'download archive failed')
	
	savedir = usr_files_dir+'/'+savedir
	yml_file = savedir+'/docker-compose.yml'
	try:
		docker_compose = base64.b64decode(docker_compose)
	except:
		docker_compose = ''
	# 如果目录已存在则清空
	if os.path.isdir(savedir):
		shutil.rmtree(savedir)
	if not os.path.isdir(savedir):
		os.mkdir(savedir)
	logging.info( 'savedir: '+savedir )
	
	exec_cmd('cp -R '+archive_cache_dir+'/* '+savedir)
	exec_cmd('chmod -R 777 '+savedir)
	
	# 删除创建锁
	lock_file = savedir+'/creating.lock'
	if os.path.exists(lock_file):
		os.remove(lock_file)
	
	if docker_compose != '':
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
			shutil.rmtree(savedir)
			return resp_json(0, 'over write docker-compose.yml failed')
	
	return resp_json(1,'',{'savedir':savedir})

def task_action(i, data):
	if not is_json(data):
		return resp_json(0, 'Invalid Data')
	params = json.loads(data)
	
	action = get_json_data_param(params, 'action')
	
	if action == 'github-download' :
		result = task_github_download(data)
	elif action == 'archive-download':
		result = task_archive_download(data)
	else:
		return resp_json(0, 'Invalid task action')
	
	result_json = json.loads(result)
	if get_json_data_param(result_json, 'status') != 1 and get_json_data_param(result_json, 'status') != '1' :
		return resp_json(0, 'download failed')
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
		self.queue = 'task:prodcons:queuedownload'
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
					#action = threading.Thread(target=action_test,args=(i, '123123123',))
					self.threads[i]['thread'] = threading.Thread(target=task_action,args=(i, data,))
					self.threads[i]['time'] = int(time.time())
					self.threads[i]['thread'].start()

if __name__ == '__main__':
	#缓存目录不存在，则创建缓存目录
	if not os.path.isdir(cache_dir):
		os.mkdir(cache_dir)
	#仍然不存在，表示创建失败
	if not os.path.isdir(cache_dir):
		raise RuntimeError('Make cache_dir failed')
	#下载目录不存在，则创建下载目录
	if not os.path.isdir(usr_files_dir):
		os.mkdir(usr_files_dir)
	#仍然不存在，表示创建失败
	if not os.path.isdir(usr_files_dir):
		raise RuntimeError('Make usr_files_dir failed')
	logging.info( '监听任务队列' )
	Task().listen_task()
		
	
