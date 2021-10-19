# gd01

## Unit Test

#### 一般測試(開發用)
```
./vendor/bin/phpunit tests/Feature/{file path}
```

#### 總測試
```
./vendor/bin/phpunit tests/Featur --testdox-html tests/TestLogs/test.html
```

```
php artisan migrate:refresh {--step=1}
```

```
php artisan make:migration modify_settlement_month_record_table --table=settlement_month_record
```

```
php artisan db:seed --class=ReportSeeder
```

### 開發備註
- excel 導出
```
新的頁面有 excel 導出需求
app\Http\Middleware\ApiResponse.php 
需新增路徑判斷, like:
if ($request->is('api/report/total/excel')) {
            return $next($request);
        }
```

- 加盟商 啟用 審核權限
```
1.角色權限管理  => 勾起要審核頁面相關權限
2.審核/通知管理 => 如果是加盟商自己要審核 加盟商那邊的設定頁面 禁止使用 公司管理
3.審核/通知管理 => 設定可審核的角色
```
