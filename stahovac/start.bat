@echo off
chcp 65001 >nul
title Stahovac videi
cd /d "%~dp0"
where py >nul 2>nul && (set PY=py -3) || (set PY=python)
%PY% --version >nul 2>nul || (
  echo Chyba Python 3. Stiahnite ho z https://www.python.org/downloads/
  echo Pri instalacii zaskrtnite "Add python.exe to PATH".
  pause & exit /b 1
)
if not exist .venv (
  echo Prve spustenie: instalujem potrebne balicky, 1-2 minuty...
  %PY% -m venv .venv || (echo Nepodarilo sa vytvorit prostredie. & pause & exit /b 1)
)
call .venv\Scripts\activate.bat
python -m pip install -q --upgrade pip
python -m pip install -q --upgrade -r requirements.txt
set STAHOVAC_OPEN_BROWSER=1
python app.py
pause
