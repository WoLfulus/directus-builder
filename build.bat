@ECHO OFF

REM Checks
REM ---------------------------------------

IF [%1] == [] GOTO :EMPTY_VERSION
IF [%2] == [] GOTO :EMPTY_API_VERSION
IF [%3] == [] GOTO :EMPTY_APP_VERSION

SET IMG_NS=wolfulus

REM Builder
REM ---------------------------------------

SET IMG_BUILDER_NAME=directus-builder
SET IMG_BUILDER_TAG=latest
SET IMG_BUILDER=%IMG_NS%/%IMG_BUILDER_NAME%:%IMG_BUILDER_TAG%

docker build -t %IMG_BUILDER% ./builder/

REM API
REM ---------------------------------------

SET IMG_API_NAME=directus-api
SET IMG_API_TAG=%1
SET IMG_API=%IMG_NS%/%IMG_API_NAME%:%IMG_API_TAG%
SET IMG_API_LATEST=%IMG_NS%/%IMG_API_NAME%:latest

docker build -t %IMG_API%^
    --build-arg "BUILDER_IMAGE=%IMG_BUILDER%"^
    --build-arg "API_VERSION=%2%"^
    ./projects/api

REM APP
REM ---------------------------------------

SET IMG_APP_NAME=directus-app
SET IMG_APP_TAG=%1
SET IMG_APP=%IMG_NS%/%IMG_APP_NAME%:%IMG_APP_TAG%
SET IMG_APP_LATEST=%IMG_NS%/%IMG_APP_NAME%:latest

docker build -t %IMG_APP%^
    --build-arg "BUILDER_IMAGE=%IMG_BUILDER%"^
    --build-arg "APP_VERSION=%3%"^
    ./projects/app

IF [%4] == [latest] GOTO :TAG_LATEST
GOTO :FINISH

REM Latest
REM ---------------------------------------

:TAG_LATEST
ECHO Tagging
docker tag %IMG_APP% %IMG_APP_LATEST%
docker tag %IMG_API% %IMG_API_LATEST%
GOTO :FINISH

REM Errors
REM ---------------------------------------

:EMPTY_VERSION
ECHO Missing version/tag argument (e.g. "7.0.1")
GOTO :EOF

:EMPTY_API_VERSION
ECHO Missing API release name (e.g. "2.0.1")
GOTO :EOF

:EMPTY_APP_VERSION
ECHO Missing APP release name (e.g. "7.0.1")
GOTO :EOF

:FINISH

ECHO Finished
