Это модуль для  работы с аудитлогом. 

version 1.0.1
Модуль получает данные об изменениях в таблицах.
На данный момент реализованы таблицы PEOPLE и SS_ACCESSUSER.
Для работы модуля необходимо добавить в базу данных таблицы:
* LOG_FIELDS
* LOG_KEYS
* LOG_TABLES
* USERCONNECTION
* AUDITLOG
для каждой таблицы необходимо реализовать генератор, а также добавить триггеры
в AUDITLOG - before insert - TR_BI_AUDITLOG
* LOG_PEOPLE_AIL
* LOG_PEOPLE_AU
* LOG_PEOPLE_AD
* LOG_SS_ACCESSUSER_AI
* LOG_SS_ACCESSUSER_AU
* LOG_SS_ACCESSUSER_AD
	
Исп. Мурашов Т.А.
27.04.2026