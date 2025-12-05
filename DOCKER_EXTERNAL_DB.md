# Docker External Database Configuration

Bu proje, `docker-datatabse-stack` stack'inde çalışan `global_mysql` ve `redis` container'larını kullanacak şekilde yapılandırılmıştır.

## Network Yapılandırması

Proje, `docker-datatabse-stack` stack'inin network'üne bağlanır:
- Network adı: `docker-datatabse-stack_default` (external network, `.env` dosyasında `EXTERNAL_NETWORK_NAME` ile değiştirilebilir)
- MySQL container: `global_mysql` (`.env` dosyasında `DB_HOST` ile değiştirilebilir)
- Redis container: `redis` (`.env` dosyasında `REDIS_HOST` ile değiştirilebilir)

## Gereksinimler

1. `docker-datatabse-stack` stack'inin çalışıyor olması gerekiyor
2. Network'ün oluşturulmuş olması gerekiyor

### Network Oluşturma

Eğer network henüz oluşturulmamışsa:

```bash
docker network create docker-datatabse-stack_default
```

Veya `docker-datatabse-stack` stack'ini başlattığınızda otomatik oluşturulur.

## Environment Variables

`.env` dosyanızda şu ayarları kullanın:

```env
# MySQL - global_mysql container'ına bağlanır
DB_CONNECTION=mysql
DB_HOST=global_mysql
DB_PORT=3306
DB_DATABASE=phone_hospital_crm
DB_USERNAME=root
DB_PASSWORD=your_password

# Redis - docker-datatabse-stack'teki redis container'ına bağlanır
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Cache ve Queue Redis kullanır
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Docker External Network
EXTERNAL_NETWORK_NAME=docker-datatabse-stack_default
```

## Container'ları Başlatma

1. Önce `docker-datatabse-stack` stack'inin çalıştığından emin olun:

```bash
cd /path/to/docker-datatabse-stack
docker-compose up -d
```

2. Bu projeyi başlatın:

```bash
cd /path/to/phonehospitalwhatsappcrm
docker-compose up -d
```

## Bağlantı Testi

### MySQL Bağlantısını Test Etme

```bash
docker exec -it phone_hospital_app php artisan tinker
```

Tinker'da:
```php
DB::connection()->getPdo();
// Bağlantı başarılıysa PDO nesnesi döner
```

### Redis Bağlantısını Test Etme

```bash
docker exec -it phone_hospital_app php artisan tinker
```

Tinker'da:
```php
Redis::connection()->ping();
// "PONG" dönerse bağlantı başarılı
```

## Sorun Giderme

### Network Bulunamadı Hatası

Eğer `network docker-datatabse-stack_default not found` hatası alırsanız:

1. Network'ün var olduğunu kontrol edin:
```bash
docker network ls | grep docker-datatabse-stack
```

2. Eğer yoksa, `docker-datatabse-stack` stack'ini başlatın veya manuel oluşturun:
```bash
docker network create docker-datatabse-stack_default
```

### Container İsimleri

Eğer `docker-datatabse-stack` stack'inizde container isimleri farklıysa:

1. Container isimlerini kontrol edin:
```bash
docker ps --format "table {{.Names}}\t{{.Image}}"
```

2. `.env` dosyasında `DB_HOST` ve `REDIS_HOST` değerlerini güncelleyin

### Bağlantı Timeout

Eğer bağlantı timeout alıyorsanız:

1. Container'ların aynı network'te olduğundan emin olun
2. Firewall ayarlarını kontrol edin
3. Container loglarını kontrol edin:
```bash
docker logs phone_hospital_app
docker logs global_mysql
docker logs redis
```

## Önemli Notlar

- MySQL ve Redis container'ları bu projenin `docker-compose.yml` dosyasında tanımlı değildir
- Bu servisler `docker-datatabse-stack` stack'inden gelir
- Proje sadece bu external servislere bağlanır
- Network bağlantısı için `database_stack_network` external network kullanılır

