# 🚀 Barbearia Brum - Backend API

Backend PHP para sistema de agendamento online da Barbearia Brum.

## 📋 Recursos

- ✅ API REST para agendamentos
- ✅ Validação completa de dados
- ✅ Rate limiting por IP
- ✅ CORS configurado para GitHub Pages
- ✅ Armazenamento em CSV
- ✅ Proteção contra spam
- ✅ Health check endpoint

## 🌐 Endpoints

### `GET /`
Informações da API
```json
{
  "nome": "Barbearia Brum - Backend API",
  "status": "online",
  "endpoints": {...}
}
```

### `POST /agendamento-online.php`
Criar novo agendamento
```json
{
  "nome": "João Silva",
  "telefone": "(11) 99999-9999",
  "data": "2024-02-15",
  "horario": "14:00",
  "servico": "Corte + Barba",
  "observacoes": "Preferência por tesoura"
}
```

### `GET /health`
Health check do servidor
```json
{
  "status": "healthy",
  "timestamp": "2024-01-24T10:30:00Z"
}
```

## 🔒 Segurança

- **CORS**: Configurado para `https://leanacleto518.github.io`
- **Rate Limiting**: 1 agendamento por minuto por IP
- **Validação**: Todos os campos validados
- **Proteção**: Diretório de dados protegido
- **Limite**: Máximo 1000 agendamentos

## 🚀 Deploy no Render

1. **Fork este repositório**
2. **Conecte ao Render**: https://render.com
3. **Configurações**:
   - Environment: `PHP`
   - Build Command: `composer install`
   - Start Command: `php -S 0.0.0.0:$PORT`
   - Plan: `Free`

## 📊 Estrutura de Dados

Os agendamentos são salvos em CSV com as colunas:
- Data/Hora Agendamento
- Nome
- Telefone
- Data Preferida
- Horário
- Serviço
- Observações
- Status
- Fonte
- IP

## 🔧 Desenvolvimento Local

```bash
# Instalar dependências
composer install

# Rodar servidor local
composer run dev
```

## 📈 Monitoramento

- **Logs**: Disponíveis no dashboard do Render
- **Health Check**: `/health` endpoint
- **Uptime**: Monitorado automaticamente

---

**Desenvolvido para Barbearia Brum** 💈