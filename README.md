# 🍳 Холодильник & Меню (Smart Fridge Chef)

Современный проект на **WordPress** в стиле **Google Material Design 3** и **Instagram Web UI** с полноценной поддержкой локальных и облачных нейросетей (**Ollama**, **Anthropic Claude**, **Google Gemini**) для анализа холодильника, составления рациона на неделю и умного подбора супермаркетов.

---

## 🚀 Быстрый запуск

Сервер работает автономно на портативном PHP 8.3 и встроенной базе данных SQLite (не требует MySQL и Docker):

1. Запустите файл **`start-server.bat`** (или `start-server.ps1`).
2. Откроется сайт: **[http://localhost:8000](http://localhost:8000)**
3. Админка для подключения Ollama / Claude: **[http://localhost:8000/wp-admin](http://localhost:8000/wp-admin)**:
   - **Логин:** `admin`
   - **Пароль:** `admin12345`

---

## 🤖 Подключение Ollama и Claude

В админке WordPress перейдите в меню **«Умный Шеф»** ([http://localhost:8000/wp-admin/admin.php?page=smart-fridge-chef](http://localhost:8000/wp-admin/admin.php?page=smart-fridge-chef)):

### 1. Ollama (Локально)
- Выберите провайдер **«Ollama»**.
- **URL сервера:** `http://localhost:11434`
- **Модель:** `llama3.2-vision`, `llava`, `claude` (proxy) или `qwen2-vl`.
- Нажмите **«Проверить подключение к ИИ»** для проверки связи в реальном времени.

### 2. Anthropic Claude
- Выберите провайдер **«Anthropic Claude»**.
- Вставьте **Claude API Key** (`sk-ant-api03-...`).
- **Модель:** `claude-3-5-sonnet-20241022` или `claude-3-haiku-20240307`.

### 3. Google Gemini
- Вставьте **Gemini API Key**.

> Если внешний ИИ временно недоступен или не настроен, приложение автоматически использует встроенный экспертный кулинарный движок без ошибок и зависаний.

---

## 🎨 Дизайн и стандарты интерфейса

Интерфейс полностью переработан в стандартном минималистичном ключе:
- **Библиотеки Google:** шрифты **Inter** и **Roboto**, оригинальные иконки **Google Material Symbols Outlined**, стили компонентов **Material Design 3**.
- **Эстетика Instagram Web:**
  - Лаконичный верхний бар с чистой типографикой и индикатором активного ИИ.
  - Сегментированная навигация со счетчиками.
  - Карточки рецептов в стиле постов Instagram (соотношение сторон, аккуратные бейджи КБЖУ, время готовки).
  - Списки покупок в стиле Google Keep (чекбоксы с вычеркиванием).
  - Раздел магазинов в стиле Google Maps Place Cards с расчетом пешей дистанции и маршрутами.
- Полностью исключены аляповатые градиенты, лазерные полосы сканирования и эмодзи-слоп.

---

## 📁 Структура проекта

```
delightful-faraday/
├── start-server.bat         # Запуск сервера и сайта в 1 клик
├── stop-server.bat          # Остановка сервера
├── tools/php/               # Портативный PHP 8.3
└── wordpress/
    └── wp-content/plugins/smart-fridge-chef/
        ├── includes/
        │   ├── class-ai-engine.php       # Контроллер Ollama / Claude / Gemini
        │   ├── class-store-locator.php   # ИИ-поиск и ранжирование магазинов
        │   ├── class-api-controller.php  # REST API эндпоинты
        │   └── class-admin-settings.php  # Панель настройки ИИ
        ├── templates/
        │   └── app-interface.php         # Чистый Material / Instagram интерфейс
        └── assets/
            ├── css/app.css               # Стили Material 3
            └── js/app.js                 # Интерактивная логика
```
