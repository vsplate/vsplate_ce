# VSPlate Community Edition

VSPlate 是一款基于 docker-compose 实现的实验平台，该项目为 www.vsplate.com 的开源版本，支持单机运行。

在线版本: 

* https://www.vsplate.com

实验环境：

* https://www.vulnspy.com
* https://github.com/vulnspy

联系邮箱：

* contact@vsplate.com

## 目录说明

* apache_html_80 控制台/用户操作界面
* apache_html_81 提供 API 供控制台调用，启动/关闭容器
* py_download_82 负责下载 Github 项目到本地
* apache2_conf Apache 配置文件

## 安装准备

1. Github OAuth2 key/secret

* 将文件 ./py_download_82/config.py 中的变量 github_client_id 和 github_client_secret 修改为您注册的 Client ID 和 Client Secret。
* 将文件 ./apache_html_80/inc/config.php 中的常量 GITHUB_CLIENT_ID 和 GITHUB_SECRET 修改为您注册的 Client ID 和 Client Secret。

2. 设置一个 Redis 密码

* 将文件 ./py_download_82/config.py 中的变量 queue_redis_pwd 修改为您设置的 Redis 密码。

3. 设置一个 apache_html_81 服务的访问密码
    
* 将文件 ./apache_html_81/inc/config.py 中的常量 Z_AUTHKEY 修改为您设置的访问密码。
* 将文件 ./apache_html_80/inc/config.php 中的常量 DOCKER_API_KEY 修改为您设置的访问密码。
   
4. 设置一个 py_download_82 服务的访问密码
    
* 将文件 ./py_download_82/config.py 中的变量 auth_key 修改为您设置的访问密码。
* 将文件 ./apache_html_80/inc/config.php 中的常量 DOWNLOAD_API_KEY 修改为您设置的访问密码。

5. 设置 mysql 数据库访问密码

* 将文件 ./apache_html_80/inc/config.php 中的常量 Z_DB_HOST、Z_DB_NAME、Z_DB_USER、Z_DB_PASSWORD 修改为对应的数据库地址、数据库名、用户名、用户密码。

## 安装

*以下命令仅在 Ubuntu 18.04 中测试过，其它系统未作测试。*

**以下命令会对系统配置和环境进行修改，建议在独立的环境中运行，以免影响其它服务运行。**

1. 下载项目源码
   
    ```bash
    git clone https://github.com/vsplate/vsplate_ce
    ```

2. 安装依赖软件

    ```bash
    sudo apt-get -y update
    sudo apt-get -y upgrade
    sudo apt-get -y install python python-requests vim supervisor apt-utils net-tools debconf-utils iputils-ping wget curl vim unzip build-essential python-pip python-flask python-redis python-tornado
    ```

3. 安装 docker 和 docker-compose

    ```bash
    sudo apt-get -y remove docker docker-engine docker.io
    sudo apt-get -y install \
        apt-transport-https \
        ca-certificates \
        curl \
        software-properties-common
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
    sudo add-apt-repository \
       "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
       $(lsb_release -cs) \
       stable"
    sudo apt-get -y update
    sudo apt-get -y install docker-ce docker-compose
    ```

4. 安装 apache+php+mysql

    ```bash
    sudo apt-get install -y apache2 php php-mysql php-pdo php-xml libapache2-mod-php mysql mysql-server
    
    # 开启 htaccess 支持
    sudo a2enmod rewrite
    sudo sed -i -r 's/AllowOverride None$/AllowOverride All/' /etc/apache2/apache2.conf
    sudo service apache2 restart
    ```

5. 创建相关 MySQL 数据库和数据表 (该步骤MySQL下操作)

    ```sql
    create database vsplate;
    use vsplate;
    source /路径/apache_html_81.sql;
    ```

6. 安装 Redis，并设置密码（密码为上面`安装准备`步骤中您设置的 Redis 密码）

    ```bash
    sudo apt install redis-server
    sudo sed -i "s/#*requirepass.*/requirepass 新密码/" /etc/redis/redis.conf
    sudo service redis restart
    ```

7. 部署相关代码文件

    **该部分涉及文件删除/覆盖等敏感操作，请注意是否会覆盖您的重要文件**

    ```bash
    sudo mkdir /docker-compose
    sudo mkdir /docker-compose/data
    sudo mkdir /var/www/data
    sudo chmod -R 777 /docker-compose
    sudo chmod -R 777 /var/www/data
    sudo rm -rf /var/www/html/

    sudo mv clean_img.py /docker-compose/clean_img.py

    sudo mv apache_html_80 /var/www/html
    sudo mv apache_html_81 /var/www/dockerapi
    sudo mv py_download_82 /var/www/
    ```

8. 将www-data加入docker组

    ```bash
    sudo usermod -aG docker www-data
    service docker restart
    ```

9. 配置 Apache 和 Supervisor

    ```bash
    sudo mv apache2_conf/ports.conf /etc/apache2/ports.conf
    sudo mv apache2_conf/dockerapi.conf /etc/apache2/dockerapi.conf
    sudo service apache2 restart

    sudo mv supervisor_vsplate.conf /etc/supervisor/conf.d/supervisor_vsplate.conf
    sudo service supervisor restart
    ```

10. 重启

    ```bash
    reboot
    ```
    
10. 安装完成

    访问 http://localhost/ ，点击 Launch 启动默认项目，启动成功表示安装成功。
