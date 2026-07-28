-- 清理无用数据表（无模型、无业务引用）
-- 已于本地库执行；其他环境可手工执行本脚本
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `xlsy_batch_apply`;
DROP TABLE IF EXISTS `xlsy_code_point`;
DROP TABLE IF EXISTS `xlsy_enterprise_apply`;
DROP TABLE IF EXISTS `xlsy_enterprise_team`;
DROP TABLE IF EXISTS `xlsy_enterprise_team_user`;
DROP TABLE IF EXISTS `xlsy_express`;
DROP TABLE IF EXISTS `xlsy_feedback`;
DROP TABLE IF EXISTS `xlsy_jobs`;
DROP TABLE IF EXISTS `xlsy_points_record`;
SET FOREIGN_KEY_CHECKS = 1;
