import { useEffect, useState } from 'react';
import { api } from '../api/client';
import ChamadoCard from '../components/ChamadoCard';

export default function PainelExecutor() {
  const [chamados, setChamados] = useState([]);
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState('');

  useEffect(() => {
    api
      .get('/relatorios/pendentes')
      .then(({ data }) => setChamados(data))
      .catch(() => setErro('Não foi possível carregar a fila.'))
      .finally(() => setCarregando(false));
  }, []);

  return (
    <div className="pagina">
      <div className="pagina-cabecalho">
        <h1>Fila de execução</h1>
      </div>
      <p className="pagina-subtitulo">Chamados já aprovados, esperando alguém assumir.</p>

      {carregando && <p>Carregando...</p>}
      {erro && <p className="erro">{erro}</p>}
      {!carregando && chamados.length === 0 && (
        <p className="vazio">Nenhum chamado esperando execução agora.</p>
      )}

      <div className="chamados-grade">
        {chamados.map((c) => (
          <ChamadoCard key={c.id} chamado={c} />
        ))}
      </div>
    </div>
  );
}
