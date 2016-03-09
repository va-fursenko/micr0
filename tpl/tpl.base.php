<div class="row log-row">
    <h4 class="log-caption">Отчёт по чему-то важному</h4>
    <img class="log-caption log-loader" id="logLoader" src="img/loader.gif">
    <a id="beginBtn" class="log-caption btn btn-sm btn-primary" href="javascript:void(0);">Поехали</a>
</div>
<div class="row">
    <pre class="log-container" id="logPre">{lines}</pre>
</div>
<div class="row notice-row">
    <h6><sup class="text-danger">*</sup>Скрипт выполняется значительное время. Не забудьте настроить сервер и PHP, а так же дать права записи на папку data/ в проекте</h6>

<pre class="notice-row">
NGNIX:
    http {
    	keepalive_timeout 240;
    }
    location ~ \.php$ {
        proxy_read_timeout   240;
        fastcgi_read_timeout 240;
    }

PHP:
    max_execution_time = 240
</pre>

</div>