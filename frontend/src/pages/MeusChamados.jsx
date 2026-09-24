import { useEffect, useState } from 'react';
import { api } from '../api/client';
import ChamadoCard from '../components/ChamadoCard';
import FiltrosChamados from '../components/FiltrosChamados';
import { useFiltros } from '../hooks/useFiltros';

export default function MeusChamados() {
  const [chamados, setChamados] = useState([]);
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState('');
  const { filtros, setFiltros, chamadosFiltrados } = useFiltros(chamados);

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

      {!carregando && chamados.length > 0 && (
        <FiltrosChamados filtros={filtros} onChange={setFiltros} />
      )}

      {carregando && <p>Carregando...</p>}
      {erro && <p className="erro">{erro}</p>}

      {!carregando && chamadosFiltrados.length === 0 && chamados.length === 0 && (
        <p className="vazio">Você ainda não abriu nenhum chamado.</p>
      )}
      {!carregando && chamadosFiltrados.length === 0 && chamados.length > 0 && (
        <p className="vazio">Nenhum chamado encontrado com esses filtros.</p>
      )}

      <div className="chamados-grade">
        {chamadosFiltrados.map((c) => (
          <ChamadoCard key={c.id} chamado={c} />
        ))}
      </div>
    </div>
  );
}