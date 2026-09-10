import { useEffect, useState } from 'react';
import { api, pythonApi } from '../api/client';
import ChamadoCard from '../components/ChamadoCard';

export default function PainelAdmin() {
  const [aba, setAba] = useState('aprovacoes');
  const [chamados, setChamados] = useState([]);
  const [slaEstourado, setSlaEstourado] = useState([]);
  const [tempoMedio, setTempoMedio] = useState([]);
  const [volume, setVolume] = useState([]);
  const [recorrencia, setRecorrencia] = useState([]);
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState('');

  useEffect(() => {
    setCarregando(true);
    setErro('');
    Promise.all([
      api.get('/solicitacoes'),
      api.get('/relatorios/sla-estourado'),
      pythonApi.get('/relatorios/tempo-medio'),
      pythonApi.get('/relatorios/volume'),
      pythonApi.get('/relatorios/recorrencia'),
    ])
      .then(([res1, res2, res3, res4, res5]) => {
        setChamados(res1.data.data || res1.data);
        setSlaEstourado(res2.data);
        setTempoMedio(res3.data);
        setVolume(res4.data);
        setRecorrencia(res5.data);
      })
      .catch(() =>
        setErro(
          'Não foi possível carregar o painel completo. Confira se a API e o serviço de relatórios estão rodando.'
        )
      )
      .finally(() => setCarregando(false));
  }, []);

  const aguardandoAprovacao = chamados.filter((c) => c.status === 'pendente_aprovacao');

  return (
    <div className="pagina">
      <div className="pagina-cabecalho">
        <h1>Painel administrativo</h1>
      </div>

      {erro && <p className="erro">{erro}</p>}

      <div className="abas">
        <button className={aba === 'aprovacoes' ? 'ativo' : ''} onClick={() => setAba('aprovacoes')}>
          Aprovações pendentes
        </button>
        <button className={aba === 'monitoramento' ? 'ativo' : ''} onClick={() => setAba('monitoramento')}>
          Monitoramento
        </button>
        <button className={aba === 'sla' ? 'ativo' : ''} onClick={() => setAba('sla')}>
          SLA estourado
        </button>
        <button className={aba === 'relatorios' ? 'ativo' : ''} onClick={() => setAba('relatorios')}>
          Relatórios
        </button>
      </div>

      {carregando && <p>Carregando painel...</p>}

      {!carregando && aba === 'aprovacoes' && (
        <div className="chamados-grade">
          {aguardandoAprovacao.length === 0 && <p className="vazio">Nenhuma aprovação pendente agora.</p>}
          {aguardandoAprovacao.map((c) => (
            <ChamadoCard key={c.id} chamado={c} />
          ))}
        </div>
      )}

      {!carregando && aba === 'monitoramento' && (
        <div className="chamados-grade">
          {chamados.length === 0 && <p className="vazio">Nenhum chamado registrado ainda.</p>}
          {chamados.map((c) => (
            <ChamadoCard key={c.id} chamado={c} />
          ))}
        </div>
      )}

      {!carregando && aba === 'sla' && (
        <div className="chamados-grade">
          {slaEstourado.length === 0 && <p className="vazio">Nenhum chamado com SLA estourado no momento.</p>}
          {slaEstourado.map((c) => (
            <ChamadoCard key={c.id} chamado={c} />
          ))}
        </div>
      )}

      {!carregando && aba === 'relatorios' && (
        <div className="relatorios">
          <div className="relatorio-bloco">
            <h2>Tempo médio de resolução</h2>
            <table className="tabela">
              <thead>
                <tr>
                  <th>Categoria</th>
                  <th>Concluídos</th>
                  <th>Tempo médio</th>
                </tr>
              </thead>
              <tbody>
                {tempoMedio.map((r) => (
                  <tr key={r.categoria}>
                    <td>{r.categoria}</td>
                    <td>{r.total_concluidos}</td>
                    <td>{r.tempo_medio_horas}h</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="relatorio-bloco">
            <h2>Volume por setor e status</h2>
            <table className="tabela">
              <thead>
                <tr>
                  <th>Setor</th>
                  <th>Status</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                {volume.map((r, i) => (
                  <tr key={i}>
                    <td>{r.setor}</td>
                    <td>{r.status}</td>
                    <td>{r.total}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="relatorio-bloco">
            <h2>O que mais se repete (deterioração)</h2>
            <p className="pagina-subtitulo">
              Palavras que aparecem em vários títulos, e a cada quantos dias voltam a surgir.
            </p>
            <table className="tabela">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Ocorrências</th>
                  <th>Média entre ocorrências</th>
                </tr>
              </thead>
              <tbody>
                {recorrencia.map((r) => (
                  <tr key={r.item}>
                    <td>{r.item}</td>
                    <td>{r.ocorrencias}</td>
                    <td>{r.media_dias_entre_ocorrencias} dias</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
