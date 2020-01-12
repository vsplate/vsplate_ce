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

debug = config.debug

queue_redis_host = config.queue_redis_host
queue_redis_pwd = config.queue_redis_pwd

monkey.patch_all()

app = Flask(__name__)

app.config.update(
    DEBUG=True
)

rcon = redis.StrictRedis(host=queue_redis_host, db=5, password=queue_redis_pwd)
prodcons_queue = 'task:prodcons:queuedownload'

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
	logging.info("timeout")

def get_git_proj_repo(addr):
	addr = addr.replace('https://github.com/', '')
	return addr.strip('/')

@set_timeout(60, after_timeout)
def exec_cmd(cmd):
	logging.info( cmd )
	return os.popen(cmd).readlines()

def md5_hash(str):
	m2 = hashlib.md5()   
	m2.update(str)   
	return m2.hexdigest()   

def resp_json(status=0, msg='', data=''):
	resp = json.dumps({'status':status, 'msg':msg, 'data':data})
	if debug:
		print status, msg, data
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
	

@app.before_request
def limit_access():
	if request.remote_addr not in allow_ip:
		abort(403)  # Forbidden
		return 'Access Denied: '+request.remote_addr
	#校验KEY
	if not request.headers.get('AUTH-KEY'):
		abort(403)  # Forbidden
		return 'Auth Required'
	#query = request.query_string()
	#data = request.get_data()
	sdp_auth = request.headers.get('AUTH-KEY')
	if sdp_auth != auth_key:
		abort(403)  # Forbidden
		return 'Auth Required'
		
@app.route('/', methods=['GET', 'POST'])
def index():
	return resp_json(1,'Hello')

@app.route('/download/github', methods=['POST'])
def download_github():
	data = request.get_data()
	logging.info( data )
	if not is_json(data):
		return resp_json(0, '1 Invalid Data')

	json_data = json.loads(data)
	savedir = get_json_data_param(json_data, 'dir')
	github_url = get_json_data_param(json_data, 'github_url')
	docker_compose = get_json_data_param(json_data, 'docker_compose')
	github_updated_at = get_json_data_param(json_data, 'github_updated_at')
	if not savedir:
		return resp_json(0,'1 Invalid Input savedir', json_data)
	if not github_url:
		return resp_json(0,'1 Invalid Input github_url', json_data)
	if not github_updated_at:
		return resp_json(0,'1 Invalid Input github_updated_at', json_data)
	# 添加到队列
	try:
		rcon.lpush(prodcons_queue, json.dumps({'action':'github-download', 'data':json_data, 'time':time.time()}))
	except:
		return resp_json(0,'Push queue failed',{'github_url':github_url,'result':''})
	return resp_json(1,'',{'github_url':github_url,'result':'pending'})

@app.route('/download/archive', methods=['POST'])
def download_archive():
	data = request.get_data()
	logging.info( data )
	if not is_json(data):
		return resp_json(0, '2 Invalid Data')

	json_data = json.loads(data)
	savedir = get_json_data_param(json_data, 'dir')
	archive_url = get_json_data_param(json_data, 'archive_url')
	docker_compose = get_json_data_param(json_data, 'docker_compose')
	if not archive_url or not docker_compose or not savedir:
		return resp_json(0,'2 Invalid Input', json_data)
	# 添加到队列
	try:
		rcon.lpush(prodcons_queue, json.dumps({'action':'archive-download', 'data':json_data, 'time':time.time()}))
	except:
		return resp_json(0,'Push queue failed',{'archive_url':archive_url,'result':''})
	return resp_json(1,'',{'archive_url':archive_url,'result':'pending'})

if __name__ == '__main__':
    #app.run(debug=False, host='0.0.0.0', port=5001)
    http_server = WSGIServer(('127.0.0.1', 82), app)
    http_server.serve_forever()
