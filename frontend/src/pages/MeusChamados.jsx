import { useEffect, useState } from 'react';
import { api } from '../api/client';
import ChamadoCard from '../components/ChamadoCard';

export default function MeusChamados() {
  const [chamados, setChamados] = useState([]);
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState('');

  useEffect(() => {
    api
      .get('/solicitacoes')
      .then(({ data }) => setChamados(data.data || data))
      .catch(() => setErro('Não foi possível carregar seus chamados.'))
      .finally(() => setCarregando(false));
  }, []);

  return (
    <div className="pagina">
      <div className="pagina-cabecalho">
        <h1>Meus chamados</h1>
      </div>

      {carregando && <p>Carregando...</p>}
      {erro && <p className="erro">{erro}</p>}

      {!carregando && chamados.length === 0 && (
        <p className="vazio">Você ainda não abriu nenhum chamado.</p>
      )}

      <div className="chamados-grade">
        {chamados.map((c) => (
          <ChamadoCard key={c.id} chamado={c} />
        ))}
      </div>
    </div>
  );
}
