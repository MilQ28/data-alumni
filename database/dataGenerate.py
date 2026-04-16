# Fake Data Generator
# python dataGenerate.py > 1000_alumni.sql

from faker import Faker
import random

fake = Faker('id_ID') # Menggunakan locale Indonesia
jurusan_list = ['Rekayasa Perangkat Lunak', 'TKJ', 'TJAT', 'Animasi']
pekerjaan_list = ['Software Engineer', 'Data Scientist', 'Network Engineer', 'UI/UX Designer', 'Web Developer']

print("INSERT INTO `alumni` (`nim`, `nama`, `angkatan`, `jurusan`, `email`, `no_hp`, `pekerjaan`, `perusahaan`, `alamat`) VALUES")

for i in range(1000):
    nim = f"553241{167 + i}"
    nama = fake.name()
    angkatan = random.randint(2018, 2024)
    jurusan = random.choice(jurusan_list)
    email = fake.free_email()
    no_hp = f"08{random.randint(1000000000, 9999999999)}"
    pekerjaan = random.choice(pekerjaan_list)
    perusahaan = fake.company()
    alamat = f"{fake.city()}"
    
    separator = "," if i < 999 else ";"
    print(f"('{nim}', '{nama}', {angkatan}, '{jurusan}', '{email}', '{no_hp}', '{pekerjaan}', '{perusahaan}', '{alamat}'){separator}")