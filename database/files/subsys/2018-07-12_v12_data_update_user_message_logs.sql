update user_message_logs
set cw_post_type = 3
where posted_context LIKE '%gateway.php?cmd=download_file%';
