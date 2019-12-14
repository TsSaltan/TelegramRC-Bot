# TelegramRC-Bot
Программа для дистанционного управления ПК через telegram

![Screenshot v2.0: Install 1](https://user-images.githubusercontent.com/3524731/70854539-4badd080-1ece-11ea-8570-6671c50236d8.png)
![Screenshot v2.0: Install 2](https://sun9-63.userapi.com/c858016/v858016715/1288de/pql91LytdUc.jpg)
![Screenshot v2.0: Main window](https://user-images.githubusercontent.com/3524731/70854609-fd4d0180-1ece-11ea-9d25-41c5876e9c7f.png)

* [**Описание программы**](https://tssaltan.top/1928.telegramrc-bot-удалённое-управление-пк-через-telegram/)
* [**Скачать программу**](https://tssaltan.top/download/1931/)

## Что нового
* Изменен внешний вид программы (в прошлой версии спойлеры ужасно лагали при редактировании)
* Поддержка прокси (пока только сервера без авторизации, DN пока не умеет корректно работать с прокси с аторизациями)
* Загрузка файлов, отправляемых пользователем в телеграм
![Upload](https://user-images.githubusercontent.com/3524731/70854678-08546180-1ed0-11ea-90ee-11fb2a20b423.png)
* Кнопки клавиатуры для удобного управления
![Buttons](https://user-images.githubusercontent.com/3524731/70854653-8d8b4680-1ecf-11ea-8067-bdff38251084.png)
![Media](https://user-images.githubusercontent.com/3524731/70854681-16a27d80-1ed0-11ea-9bf1-60fc5294dc9e.png)
* Усовершенствован просмотр файлов (/ls)
![File Explorer](https://user-images.githubusercontent.com/3524731/70854658-ac89d880-1ecf-11ea-9199-e990b0fc9299.png)
* Отправка на печать файлов (windows only + необходим установленный microsoft word)
![Print](https://user-images.githubusercontent.com/3524731/70854668-d511d280-1ecf-11ea-9a5d-411801cdee16.png)
* Другая логика кнопок закрытия окна: закрыть - полностью закрывает программу, свернуть - сворачивает в трей.

## Build
Для сборки версии 2.0 необходимы пакеты:
* [Windows 2.1](https://github.com/TsSaltan/DevelNext-Windows/releases/tag/2.1)
* [Webcam 1.0.2](https://github.com/jphp-group/jphp-webcam-ext/releases/tag/1.0.3)
* SystemTray 1.0 (в составе DevelNext)

Для 1.0 также необходим пакет [Telegram Bot API 1.0](https://github.com/broelik/jphp-telegram-bot-api/releases/tag/1.0.0), в версии 2.0 он изменен и уже прикреплен к проекту.
