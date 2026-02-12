<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class UpdateSchema extends Command
{
    protected function configure()
    {
        $this->setName('update_schema')
            ->setDescription('Update database schema for Merchant features');
    }

    protected function execute(Input $input, Output $output)
    {
        $prefix = 'yoshop_';

        try {
            // 1. Add merchant_id to store_user
            $output->writeln("Checking store_user table...");
            $columns = Db::query("SHOW COLUMNS FROM {$prefix}store_user");
            $hasMerchantId = false;
            foreach ($columns as $col) {
                if ($col['Field'] === 'merchant_id') {
                    $hasMerchantId = true;
                    break;
                }
            }
            if (!$hasMerchantId) {
                Db::execute("ALTER TABLE {$prefix}store_user ADD COLUMN `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户ID' AFTER `store_id`");
                $output->writeln("Added merchant_id to store_user.");
            } else {
                $output->writeln("merchant_id already exists in store_user.");
            }

            // 2. Add audit_status to goods
            $output->writeln("Checking goods table...");
            $columns = Db::query("SHOW COLUMNS FROM {$prefix}goods");
            $hasAuditStatus = false;
            foreach ($columns as $col) {
                if ($col['Field'] === 'audit_status') {
                    $hasAuditStatus = true;
                    break;
                }
            }
            if (!$hasAuditStatus) {
                Db::execute("ALTER TABLE {$prefix}goods ADD COLUMN `audit_status` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '审核状态(0待审核 10已通过 20未通过)' AFTER `status`");
                $output->writeln("Added audit_status to goods.");
            } else {
                $output->writeln("audit_status already exists in goods.");
            }
            
            // 3. Add status to merchant
            $output->writeln("Checking merchant table...");
            $columns = Db::query("SHOW COLUMNS FROM {$prefix}merchant");
            $hasStatus = false;
            foreach ($columns as $col) {
                 if ($col['Field'] === 'status') {
                    $hasStatus = true;
                    break;
                }
            }
            if (!$hasStatus) {
                 Db::execute("ALTER TABLE {$prefix}merchant ADD COLUMN `status` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '状态(0待审核 10已通过 20未通过)' AFTER `user_id`");
                 $output->writeln("Added status to merchant.");
            } else {
                $output->writeln("status already exists in merchant.");
            }

            // 4. Create Role 10004
            $output->writeln("Checking store_role table...");
            $role = Db::table("{$prefix}store_role")->where('role_id', 10004)->find();
            if (!$role) {
                // Find a valid store_id (e.g., 10001)
                $store = Db::table("{$prefix}store")->order('store_id', 'asc')->find();
                $storeId = $store ? $store['store_id'] : 10001;

                Db::table("{$prefix}store_role")->insert([
                    'role_id' => 10004,
                    'role_name' => '商家管理员',
                    'store_id' => $storeId, 
                    'sort' => 100,
                    'create_time' => time(),
                    'update_time' => time()
                ]);
                $output->writeln("Created role 10004.");
            } else {
                 $output->writeln("Role 10004 already exists.");
            }
            
            $output->writeln("Schema update completed successfully.");

        } catch (\Exception $e) {
            $output->writeln("Error: " . $e->getMessage());
        }
    }
}
