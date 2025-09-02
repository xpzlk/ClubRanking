@echo off
setlocal

rem Vérification du paramètre
if "%~1"=="" (
    echo Usage: %0 nom_du_fichier.php
    echo Exemple: %0 test.php
    exit /b 1
)

rem Récupération du nom de fichier depuis le premier paramètre
set "PHP_FILE=%~1"

rem Répertoire de génération local (répertoire courant)
set "GEN_DIR=%cd%"

rem Vérification de l'existence du fichier
if not exist "%GEN_DIR%\%PHP_FILE%" (
    echo ERREUR : Le fichier %PHP_FILE% n'existe pas dans %GEN_DIR%
    exit /b 1
)

rem ——————————————————————————————————————————
rem Appel de Docker pour générer le planning
rem ——————————————————————————————————————————
echo Execution de %PHP_FILE%...
docker run -it --rm ^
    -v "%GEN_DIR%:/app" ^
    -w /app ^
    php-runner %PHP_FILE%

if errorlevel 1 (
    echo ERREUR : echec de %PHP_FILE%
    exit /b 1
)

echo %PHP_FILE% execute avec succes.