@echo off
cd /d T:\laragon\www\vakil
echo === בונה assets ===
call npm run build

echo === מעלה לגיטהאב ===
git add .
git commit -m "deploy %date% %time%"
git push origin main

echo === מעלה build לשרת ===
powershell -Command "scp -P 65002 -i 'T:\.ssh\vakil_deploy' -r 'T:\laragon\www\vakil\public\build' u630483490@46.17.175.26:~/domains/vakil.chepti.com/public_html/build"

echo === מושך בשרת ===
powershell -Command "ssh -p 65002 -i 'T:\.ssh\vakil_deploy' u630483490@46.17.175.26 'cd ~/domains/vakil.chepti.com && git pull origin main'"

echo === סיום! ===
pause
