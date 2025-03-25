@echo off
:loop
php cmd.php "class=%1&method=%2"
goto loop
