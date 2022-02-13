@echo off
SET PROJECT=test

cd %~dp0
del /q %PROJECT%.*
bin\sjasmplus.exe --lst=%PROJECT%.lst --inc=sources\. sources\%PROJECT%.asm

IF NOT EXIST %PROJECT%.sna GOTO ERROR

bin\unreal\unreal.exe %PROJECT%.sna
GOTO END

:ERROR
EXIT 1

:END