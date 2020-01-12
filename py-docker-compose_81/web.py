#coding:utf-8

from flask import Flask, jsonify, request, abort
import json, re, os, hashlib, shutil, tempfile, datetime, stat, base64, logging
import signal, time
import urllib2
import redis
import config

from gevent import monkey
from gevent.pywsgi import WSGIServer

auth_key = config.auth_key
allow_ip = config.allow_ip
data_dir = '/py-docker-compose/data'

queue_redis_host = config.queue_redis_host
queue_redis_pwd = config.queue_redis_pwd

monkey.patch_all()

app = Flask(__name__)

app.config.update(
    DEBUG=True
)

rcon = redis.StrictRedis(host=queue_redis_host, db=5, password=queue_redis_pwd)
prodcons_queue = 'task:prodcons:queue'

def set_timeout(num, callback):
	def wrap(func):  
		def handle(signum, frame):  # 收到信号 SIGALRM 后的回调函数，第一个参数是信号的数字，第二个参数是the interrupted stack frame.  
			raise RuntimeError  
  
		def to_do(*args, **kwargs):  
			try:  
				signal.signal(signal.SIGALRM, handle)  # 设置信号和回调函数  
				signal.alarm(num)  # 设置 num 秒的闹钟  
				logging.info( 'start alarm signal.' )  
				r = func(*args, **kwargs)  
				logging.info( 'close alarm signal.' ) 
				signal.alarm(0)  # 关闭闹钟  
				return r  
			except RuntimeError as e:  
				callback()  
	  
		return to_do  
  
	return wrap

def after_timeout():  # 超时后的处理函数  
	logging.info( "timeout" )

@set_timeout(60, after_timeout)
def exec_cmd(cmd):
	print cmd
	logging.info( cmd )
	return os.popen(cmd).readlines()

def md5_hash(str):
	m2 = hashlib.md5()   
	m2.update(str)   
	return m2.hexdigest()   

def resp_json(status=0, msg='', data=''):
	resp = json.dumps({'status':status, 'msg':msg, 'data':data})
	if status == 0:
		print resp
	logging.info( resp )
	return resp

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

def is_filestr(str):
	if str == None:
		return False
	p = re.compile('^[0-9a-zA-Z_\/\.@\-:]+$')
	if p.match(str):
		return True
	else:
		return False

def get_yml_file(project):
	if not is_validstr(project):
		return False
	return data_dir+"/"+project+"/docker-compose.yml"

def get_proj_dir(project):
	if not is_validstr(project):
		return False
	return data_dir+"/"+project
	

@app.before_request
def limit_access():
	if request.remote_addr not in allow_ip:
		abort(403)  # Forbidden
		return 'Access Denied: '+request.remote_addr
	#校验KEY
	if not request.headers.get('AUTH-KEY'):
		abort(403)  # Forbidden
		return 'Auth Required'
	sdp_auth = request.headers.get('AUTH-KEY')
	if sdp_auth != auth_key:
		abort(403)  # Forbidden
		return 'Auth Required'
		
@app.route('/', methods=['GET', 'POST'])
def index():
	if not os.system('/etc/init.d/docker status') == 0:
		return resp_json(0,'',{'docker':'down'})
	psef = exec_cmd("ps -ef | grep python");
	return resp_json(1,'',{'docker':'running','psef':psef})
	
@app.route('/docker-compose/archive-up', methods=['POST'])
def docker_compose_archive_up():
	data = request.get_data()
	logging.info( data )
	print data
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')

	json_data = json.loads(data)
	project = get_json_data_param(json_data, 'project')
	archive_url = get_json_data_param(json_data, 'archive_url')
	docker_compose = get_json_data_param(json_data, 'docker_compose')
	if not is_validstr(project):
		return resp_json(0, request.path+': invalid project name')
	
	# 添加到队列
	try:
		rcon.lpush(prodcons_queue, json.dumps({'action':'archive-up', 'data':json_data, 'time':time.time()}))
		print "new action archive-up: "+project
	except:
		return resp_json(0,request.path+': Error',{'project':project,'result':''})
	
	return resp_json(1,'',{'project':project,'result':''})

@app.route('/docker-compose/rm', methods=['POST'])
def docker_compose_rm():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	project = get_json_data_param(json_data, 'project')
	if not is_validstr(project):
		return resp_json(0, request.path+': invalid project name')
	yml_file = get_yml_file(project)
	proj_dir = get_proj_dir(project)
	result = exec_cmd('test -f '+yml_file+' && docker-compose -f '+yml_file+' -p '+project+' down && rm -rf '+proj_dir)
	return resp_json(1,'',{'project':project,'result':result})

@app.route('/docker-compose/isupdone', methods=['POST'])
def docker_compose_isupdone():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	project = get_json_data_param(json_data, 'project')
	if not is_validstr(project):
		return resp_json(0, request.path+': invalid project name')
	yml_file = get_yml_file(project)
	proj_dir = get_proj_dir(project)
	done_file = proj_dir+"/vs_done.txt"
	if not os.path.exists(yml_file) or not os.path.exists(done_file):
		return resp_json(0,'Not done')
	result = ''
	file_object = open(done_file)
	try:
		result = file_object.read()
	finally:
		file_object.close()
	return resp_json(1,'',{'project':project,'result':result})

@app.route('/docker-compose/uplogs', methods=['POST'])
def docker_compose_uplogs():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	project = get_json_data_param(json_data, 'project')
	if not is_validstr(project):
		return resp_json(0, request.path+': invalid project name')
	yml_file = get_yml_file(project)
	proj_dir = get_proj_dir(project)
	log_file = yml_file+"/docker-compose.log"
	if not os.path.exists(yml_file) or not os.path.exists(log_file):
		return resp_json(0,'Not exists log file')
	result = ''
	file_object = open(log_file)
	try:
		result = file_object.read()
	finally:
		file_object.close()
	return resp_json(1,'',{'project':project,'result':result})

@app.route('/docker-compose/isexists', methods=['POST'])
def docker_compose_isexists():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	project = get_json_data_param(json_data, 'project')
	if not is_validstr(project):
		return resp_json(0, request.path+': invalid project name')
	yml_file = get_yml_file(project)
	proj_dir = get_proj_dir(project)
	if not os.path.exists(yml_file):
		return resp_json(0,'Not exists')
	return resp_json(1,'Exists')

@app.route('/docker-compose/ps', methods=['POST'])
def docker_compose_ps():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	project = get_json_data_param(json_data, 'project')
	if not is_validstr(project):
		return resp_json(0, request.path+': invalid project name')
	yml_file = get_yml_file(project)
	result = exec_cmd('test -f '+yml_file+' && docker-compose -f '+yml_file+' -p '+project+' ps')
	return resp_json(1,'',{'project':project,'result':result})

@app.route('/docker-compose/all', methods=['POST'])
def docker_compose_all():
	if not os.path.isdir(data_dir):
		return resp_json(0, request.path+': Invalid Dir')
	dir = os.listdir(data_dir)
	result = []
	for d in dir:
		result.append(d) 
	
	return resp_json(1,'',{'result':result})

@app.route('/docker/restart', methods=['POST'])
def docker_restart():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	name = get_json_data_param(json_data, 'name')
	if not is_validstr(name):
		return resp_json(0, request.path+': invalid id or invalid name')
	result = exec_cmd('docker inspect '+name+' >/dev/null 2>&1 && docker restart '+name)
	return resp_json(1,'',{'name':name,'result':result})

@app.route('/docker/start', methods=['POST'])
def docker_start():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, 'Invalid Data')
	
	json_data = json.loads(data)
	name = get_json_data_param(json_data, 'name')
	if not is_validstr(name):
		return resp_json(0, request.path+': invalid id or invalid name')
	result = exec_cmd('docker inspect '+name+' >/dev/null 2>&1 && docker start '+name)
	return resp_json(1,'',{'name':name,'result':result})

@app.route('/docker/stop', methods=['POST'])
def docker_stop():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	name = get_json_data_param(json_data, 'name')
	if not is_validstr(name):
		return resp_json(0, request.path+': invalid id or invalid name')
	result = exec_cmd('docker inspect '+name+' >/dev/null 2>&1 && docker stop '+name)
	return resp_json(1,'',{'name':name,'result':result})

@app.route('/docker/remove', methods=['POST'])
def docker_remove():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	name = get_json_data_param(json_data, 'name')
	if not is_validstr(name):
		return resp_json(0, request.path+': invalid id or invalid name')
	result = exec_cmd('docker inspect '+name+' >/dev/null 2>&1 && docker rm '+name)
	return resp_json(1,'',{'name':name,'result':result})

@app.route('/docker/inspect', methods=['POST'])
def docker_inspect():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	name = get_json_data_param(json_data, 'name')
	if not is_validstr(name):
		return resp_json(0, request.path+': invalid id or invalid name')
	result = exec_cmd('docker inspect '+name)
	return resp_json(1,'',{'name':name,'result':result})

@app.route('/docker/exec', methods=['POST'])
def docker_execute():
	data = request.get_data()
	if not is_json(data):
		return resp_json(0, request.path+': Invalid Data')
	
	json_data = json.loads(data)
	name = get_json_data_param(json_data, 'name')
	command = get_json_data_param(json_data, 'command')
	logging.info( "Exec: "+command )
	command_b64 = base64.b64encode(command)
	command = 'echo '+command_b64+' | base64 -d | /bin/sh';
	if not is_validstr(name):
		return resp_json(0, 'invalid id or invalid name')
	result = exec_cmd('docker inspect '+name+' >/dev/null 2>&1 && docker exec -d '+name+" bash -c '"+command.replace("'", "'\"'\"'")+"'")
	return resp_json(1,'',{'name':name,'result':result})

@app.route('/docker/ps', methods=['POST'])
def docker_ps():
	result = exec_cmd('docker ps')
	return resp_json(1,'',{'result':result})

@app.route('/docker/ps-a', methods=['POST'])
def docker_psa():
	result = exec_cmd('docker ps -a')
	return resp_json(1,'',{'result':result})

if __name__ == '__main__':
    http_server = WSGIServer(('0.0.0.0', 81), app)
    http_server.serve_forever()
