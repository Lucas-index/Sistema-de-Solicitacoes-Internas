import { useEffect, useState } from 'react';
import { api } from '../api/client';

export default function NotificationsBell() {
  const [notificacoes, setNotificacoes] = useState([]);
  const [aberto, setAberto] = useState(false);

  async function carregar() {
    try {
      const { data } = await api.get('/notificacoes');
      setNotificacoes(data);
    } catch (err) {
      // silencioso: o sino não deve travar o resto da tela se falhar
    }
  }

  useEffect(() => {
    carregar();
    const intervalo = setInterval(carregar, 30000);
    return () => clearInterval(intervalo);
  }, []);

  const naoLidas = notificacoes.filter((n) => !n.lida).length;

  async function marcarLida(id) {
    await api.post(`/notificacoes/${id}/marcar-lida`);
    setNotificacoes((atual) => atual.map((n) => (n.id === id ? { ...n, lida: true } : n)));
  }

  return (
    <div className="notificacoes">
      <button className="sino" onClick={() => setAberto((v) => !v)}>
        Notificações
        {naoLidas > 0 && <span className="sino-contador">{naoLidas}</span>}
      </button>

      {aberto && (
        <div className="notificacoes-lista">
          {notificacoes.length === 0 && <p className="notificacoes-vazio">Nenhuma notificação ainda.</p>}
          {notificacoes.map((n) => (
            <button
              key={n.id}
              className={`notificacao-item ${n.lida ? '' : 'nao-lida'}`}
              onClick={() => marcarLida(n.id)}
            >
              {n.mensagem}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
