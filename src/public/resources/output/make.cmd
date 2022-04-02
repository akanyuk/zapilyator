@echo off
SET PROJECT=zapil

cd %~dp0
del /q %PROJECT%.*
sjasmplus.exe --inc=sources\. sources\%PROJECT%.asm

IF NOT EXIST %PROJECT%.sna GOTO ERROR

GOTO END

:ERROR
EXIT 1

:END