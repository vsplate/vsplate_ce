# PY DOWNLOAD

平台下载项目代码

---

### 下载Github项目

1. 检查是否含有docker-compose.yml
2. 检查docker-compose.yml是否含有volumes
3. 如果没有volumes只需下载docker-compose.yml
4. 如果含有volumes检查项目大小，下载整个项目
5. 替换过滤的docker-compose.yml，并将压缩包给启动项目。

POST: /download/github

{
   "dir":"保存的目录",
   "github_url":"github项目地址",
   "docker_compose":"过滤后yml内容base64编码",
   "github_updated_at":"最后更新日期"
}

RESP: 

* 成功：{"status":1, "msg":"", "data":""} 
* 失败：{"status":0, "msg":"失败原因", "data":""}

### 下载压缩包

下载项目压缩包，检查压缩包哈希，检查是否含有docker-compose.yml，供启动项目使用

POST: /download/archive

{"dir":"保存的目录","archive_url":"压缩包地址","docker_compose":"过滤后yml内容base64编码"}

RESP: 

* 成功：{"status":1, "msg":"", "data":""} 
* 失败：{"status":0, "msg":"失败原因", "data":""}
