import socket
import threading
import os

SERVER = ('127.0.0.1', 5000)
CHUNK_SIZE = 1024

sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)


def register(name):
    sock.sendto(f'REGISTER|{name}'.encode(), SERVER)


def send_message(text):
    sock.sendto(f'MSG|{text}'.encode(), SERVER)


def send_file(path):
    filename = os.path.basename(path)
    size = os.path.getsize(path)
    sock.sendto(f'FILE_START|{filename}:{size}'.encode(), SERVER)

    with open(path, 'rb') as f:
        seq = 0
        while chunk := f.read(CHUNK_SIZE):
            packet = f'FILE_CHUNK|{seq}:'.encode() + chunk
            sock.sendto(packet, SERVER)
            seq += 1

    sock.sendto(f'FILE_END|{filename}'.encode(), SERVER)


def receive_loop():
    incoming_files = {}
    while True:
        data, _ = sock.recvfrom(CHUNK_SIZE + 128)
        header, _, payload = data.partition(b'|')
        ptype = header.decode(errors='ignore')

        if ptype == 'MSG':
            print('Сообщение:', payload.decode())

        elif ptype == 'FILE_START':
            name, size = payload.decode().split(':')
            incoming_files[name] = bytearray()

        elif ptype == 'FILE_CHUNK':
            seq_str, _, chunk = payload.partition(b':')
            # для простоты файл записывается в порядке получения;
            # для строгого порядка нужно сортировать по seq_str
            for name in incoming_files:
                incoming_files[name].extend(chunk)

        elif ptype == 'FILE_END':
            name = payload.decode()
            if name in incoming_files:
                with open(f'received_{name}', 'wb') as f:
                    f.write(incoming_files.pop(name))
                print(f'Файл {name} получен и сохранён')

        elif ptype == 'ACK':
            pass


if __name__ == '__main__':
    nickname = input('Введите имя: ')
    register(nickname)
    threading.Thread(target=receive_loop, daemon=True).start()

    while True:
        cmd = input()
        if cmd.startswith('/file '):
            send_file(cmd[6:].strip())
        else:
            send_message(cmd)