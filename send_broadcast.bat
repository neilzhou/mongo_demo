@echo off
set workDir=%~dp0
cd "%workDir%app"
%workDir%lib\Cake\Console\cake Broadcast
pause
