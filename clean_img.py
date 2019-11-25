#coding:utf-8

import os,time

def exec_cmd(cmd):
	return os.popen(cmd).readlines()
	
# 删除非vsplate的镜像
if __name__ == '__main__':
	while 1:
		exec_cmd('docker rmi `docker images --format "{{.ID}}: {{.Repository}}" | grep -v vsplate/ | grep -v mysql | grep -v php  | cut -c1-12`')
		exec_cmd('docker volume ls -qf dangling=true | xargs -r docker volume rm')
		exec_cmd('docker network prune -f')
		time.sleep(100)
