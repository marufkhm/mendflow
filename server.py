import socket
import threading

HOST = '0.0.0.0'
PORT = 5000
BUFFER_SIZE = 4096

server_socket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
server_socket.bind((HOST, PORT))

clients = {}   # имя клиента -> адрес (ip, port)
lock = threading.Lock()


def send(data, addr):
    server_socket.sendto(data, addr)


def broadcast(data, sender_addr):
    with lock:
        for addr in clients.values():
            if addr != sender_addr:
                send(data, addr)


def handle_packet(data, addr):
    header, _, payload = data.partition(b'|')
    ptype = header.decode(errors='ignore')
    print(f'Получен пакет {ptype!r} от {addr}')

    if ptype == 'REGISTER':
        name = payload.decode()
        with lock:
            clients[name] = addr
        send(f'ACK|Добро пожаловать, {name}'.encode(), addr)

    elif ptype == 'MSG':
        broadcast(data, addr)

    elif ptype in ('FILE_START', 'FILE_CHUNK', 'FILE_END'):
        # содержимое файла пересылается получателю без разбора на сервере
        broadcast(data, addr)
        send(b'ACK|', addr)

    else:
        send('ERROR|Неизвестный тип пакета'.encode(), addr)


def main():
    print(f'UDP-сервер запущен на порту {PORT}')
    while True:
        data, addr = server_socket.recvfrom(BUFFER_SIZE)
        threading.Thread(target=handle_packet, args=(data, addr), daemon=True).start()


if __name__ == '__main__':
    main()