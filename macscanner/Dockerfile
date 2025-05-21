FROM python:3.11-slim

RUN apt-get update && apt-get install -y \
    arp-scan \
    nmap \
    iproute2 \
    net-tools \
    sudo \
    iputils-ping \
    && rm -rf /var/lib/apt/lists/*

RUN pip install flask wakeonlan

COPY app.py /app/app.py
WORKDIR /app

EXPOSE 5000

CMD ["python", "app.py"] 