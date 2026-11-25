FROM python:3.13-slim
RUN apt-get update && apt-get install -y --no-install-recommends ffmpeg && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY . .
ENV PORT=8080
EXPOSE 8080
# jeden worker – úlohy sťahovania sa držia v pamäti procesu
CMD gunicorn -w 1 --threads 8 --timeout 0 -b 0.0.0.0:$PORT app:app
