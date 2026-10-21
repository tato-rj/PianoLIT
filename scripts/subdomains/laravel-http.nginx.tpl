# Managed by pianolit-subdomain: {{domain}}
server {
    listen 80;
{{ipv6_listener}}
    server_name {{domain}};
    root {{document_root}};
    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt { access_log off; log_not_found off; }
    error_page 404 /index.php;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:{{php_fpm_socket}};
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
