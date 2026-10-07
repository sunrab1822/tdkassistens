# syntax=docker/dockerfile:1

########## 1) Composer vendor ##########
FROM php:8.2-cli-bookworm AS vendor

RUN apt-get update && apt-get install -y --no-install-recommends unzip git \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY TDKAsszisztens/composer.json TDKAsszisztens/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist

########## 2) Frontend assets (Vite) ##########
FROM node:20-bookworm-slim AS frontend

WORKDIR /app
COPY TDKAsszisztens/package.json TDKAsszisztens/package-lock.json ./
RUN npm ci

# az app.ts a vendor/tightenco/ziggy-ből importálja a ZiggyVue-t
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy

COPY TDKAsszisztens/vite.config.ts TDKAsszisztens/tsconfig.json \
     TDKAsszisztens/tailwind.config.js TDKAsszisztens/components.json ./
COPY TDKAsszisztens/resources ./resources
COPY TDKAsszisztens/public ./public
RUN npm run build

########## 3) Végleges image ##########
FROM php:8.2-fpm-bookworm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        python3 \
        python3-venv \
        python3-pip \
        tesseract-ocr \
        tesseract-ocr-hun \
        curl \
        ca-certificates \
        sqlite3 \
        zstd \
    && rm -rf /var/lib/apt/lists/*

# Ollama (a legújabb stabil verzió; pinninghez: OLLAMA_VERSION env az install.sh elé)
RUN curl -fsSL https://ollama.com/install.sh | sh

# Python venv + CPU-only torch (sokkal kisebb, mint a CUDA-s wheel) + requirements
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH" \
    HF_HOME=/opt/hf-home

COPY RAG/requirements.txt /tmp/requirements.txt
RUN pip install --no-cache-dir --upgrade pip typing_extensions \
    && pip install --no-cache-dir torch --index-url https://download.pytorch.org/whl/cpu \
    && pip install --no-cache-dir -r /tmp/requirements.txt \
    && python -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')" \
    && chmod -R a+rwX /opt/hf-home

# Alkalmazás kód
COPY RAG /app/rag
COPY TDKAsszisztens /var/www
COPY --from=vendor /app/vendor /var/www/vendor
COPY --from=frontend /app/public/build /var/www/public/build

# Konténer configok
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.sh docker/pull-models.sh /docker/
RUN chmod +x /docker/*.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost:8080 \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=database \
    LOG_CHANNEL=stderr \
    RAG_API_URL=http://127.0.0.1:8000 \
    OLLAMA_BASE_URL=http://127.0.0.1:11434/v1 \
    CHROMA_DATA_DIR=/data/chroma \
    TESSERACT_CMD=tesseract \
    OLLAMA_MODELS=/data/ollama/models

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && cd /var/www && php artisan package:discover --ansi

EXPOSE 80
VOLUME /data

ENTRYPOINT ["/docker/entrypoint.sh"]
