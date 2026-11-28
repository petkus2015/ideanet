FROM python:3.12-slim
RUN apt-get update && apt-get install -y --no-install-recommends ffmpeg fontconfig \
    fonts-inter fonts-liberation fonts-dejavu-core fonts-crosextra-carlito fonts-crosextra-caladea \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
ARG TITULKY_MODEL=small
ENV TITULKY_MODEL=${TITULKY_MODEL}
# model sa stiahne už pri zostavení, aby prvé rozpoznanie nečakalo
RUN python -c "from faster_whisper import WhisperModel; WhisperModel('${TITULKY_MODEL}', device='cpu', compute_type='int8')"
COPY server.py asr.py render.py ./
COPY static static
ENV HOST=0.0.0.0 PORT=8000 TITULKY_DATA=/data
EXPOSE 8000
CMD ["python", "server.py"]
