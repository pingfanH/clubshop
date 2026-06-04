#!/bin/bash
# 数据库自动迁移脚本

MYSQL_CONTAINER="yoshop2-mysql"
MYSQL_USER="yoshop2"
MYSQL_PASSWORD="yoshop2"
MYSQL_DATABASE="yoshop2"
MIGRATIONS_DIR="/Users/pingfanh/project/clubShop/yoshop2.0/database/migrations"

echo "开始执行数据库迁移..."

for sql_file in "$MIGRATIONS_DIR"/*.sql; do
    if [ -f "$sql_file" ]; then
        filename=$(basename "$sql_file")
        echo "执行: $filename"
        docker exec -i $MYSQL_CONTAINER mysql -u $MYSQL_USER -p"$MYSQL_PASSWORD" $MYSQL_DATABASE < "$sql_file" 2>/dev/null
        if [ $? -eq 0 ]; then
            echo "✓ 成功"
        else
            echo "✗ 失败"
        fi
    fi
done

echo ""
echo "验证表结构..."
docker exec -i $MYSQL_CONTAINER mysql -u $MYSQL_USER -p"$MYSQL_PASSWORD" $MYSQL_DATABASE -e "SHOW TABLES LIKE 'yoshop_%';" 2>/dev/null

echo ""
echo "迁移完成！"
