import { useEffect, useState } from 'react';
import { api, pythonApi } from '../api/client';
import ChamadoCard from '../components/ChamadoCard';
import FiltrosChamados from '../components/FiltrosChamados';
import { useFiltros } from '../hooks/useFiltros';

export default function PainelAdmin() {
  const [aba, setAba] = useState('aprovacoes');
  const [chamados, setChamados] = useState([]);
  const [slaEstourado, setSlaEstourado] = useState([]);
  const [triagemManual, setTriagemManual] = useState([]);
  const [qualidade, setQualidade] = useState(null);
  const [correcoes, setCorrecoes] = useState([]);
  const [tempoMedio, setTempoMedio] = useState([]);
  const [volume, setVolume] = useState([]);
  const [recorrencia, setRecorrencia] = useState([]);
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState('');
  const { filtros: filtrosMonitor, setFiltros: setFiltrosMonitor, chamadosFiltrados: chamadosMonitor } = useFiltros(chamados);

  useEffect(() => {
    setCarregando(true);
    setErro('');
    Promise.all([
      api.get('/solicitacoes'),
      api.get('/relatorios/sla-estourado'),
      api.get('/relatorios/triagem-manual'),
      api.get('/relatorios/qualidade-classificacao'),
      api.get('/relatorios/correcoes'),
      pythonApi.get('/relatorios/tempo-medio'),
      pythonApi.get('/relatorios/volume'),
      pythonApi.get('/relatorios/recorrencia'),
    ])
      .then(([res1, res2, res3, res4, res5, res6, res7, res8]) => {
        setChamados(res1.data.data || res1.data);
        setSlaEstourado(res2.data);
        setTriagemManual(res3.data);
        setQualidade(res4.data);
        setCorrecoes(res5.data);
        setTempoMedio(res6.data);
        setVolume(res7.data);
        setRecorrencia(res8.data);
      })
      .catch(() =>
        setErro(
          'Não foi possível carregar o painel completo. Confira se a API e o serviço de relatórios estão rodando.'
        )
      )
      .finally(() => setCarregando(false));
  }, []);

  async function baixarExcel() {
    const resposta = await pythonApi.get('/relatorios/exportar-excel', { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([resposta.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'relatorio_solicitacoes.xlsx');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  }

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
        <button className={aba === 'triagem' ? 'ativo' : ''} onClick={() => setAba('triagem')}>
          Triagem manual
        </button>
        <button className={aba === 'monitoramento' ? 'ativo' : ''} onClick={() => setAba('monitoramento')}>
          Monitoramento
        </button>
        <button className={aba === 'sla' ? 'ativo' : ''} onClick={() => setAba('sla')}>
          SLA estourado
        </button>
        <button className={aba === 'qualidade' ? 'ativo' : ''} onClick={() => setAba('qualidade')}>
          Qualidade da IA
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

      {!carregando && aba === 'triagem' && (
        <div className="chamados-grade">
          <p className="pagina-subtitulo" style={{ gridColumn: '1 / -1', margin: 0 }}>
            Chamados que a IA não conseguiu classificar com confiança suficiente. Abra cada um para corrigir manualmente.
          </p>
          {triagemManual.length === 0 && <p className="vazio">Nenhum chamado esperando triagem manual.</p>}
          {triagemManual.map((c) => (
            <ChamadoCard key={c.id} chamado={c} />
          ))}
        </div>
      )}

      {!carregando && aba === 'monitoramento' && (
  <div>
    <FiltrosChamados filtros={filtrosMonitor} onChange={setFiltrosMonitor} />
    <div className="chamados-grade" style={{ marginTop: 16 }}>
      {chamadosMonitor.length === 0 && <p className="vazio">Nenhum chamado encontrado.</p>}
      {chamadosMonitor.map((c) => (
        <ChamadoCard key={c.id} chamado={c} />
      ))}
    </div>
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

      {!carregando && aba === 'qualidade' && qualidade && (
        <div>
          <div className="chamados-grade" style={{ marginBottom: 24 }}>
            <div className="relatorio-bloco">
              <h2>Total classificado pela IA</h2>
              <p style={{ fontSize: '1.8rem', fontWeight: 600, margin: 0 }}>{qualidade.total_classificados}</p>
            </div>
            <div className="relatorio-bloco">
              <h2>Classificado automaticamente</h2>
              <p style={{ fontSize: '1.8rem', fontWeight: 600, margin: 0 }}>{qualidade.total_automatico}</p>
            </div>
            <div className="relatorio-bloco">
              <h2>Aguardando triagem manual</h2>
              <p style={{ fontSize: '1.8rem', fontWeight: 600, margin: 0 }}>{qualidade.total_aguardando_manual}</p>
            </div>
            <div className="relatorio-bloco">
              <h2>Correções feitas</h2>
              <p style={{ fontSize: '1.8rem', fontWeight: 600, margin: 0 }}>{qualidade.total_correcoes}</p>
            </div>
            <div className="relatorio-bloco">
              <h2>Confiança média (categoria)</h2>
              <p style={{ fontSize: '1.8rem', fontWeight: 600, margin: 0 }}>
                {Math.round(qualidade.confianca_media_categoria * 100)}%
              </p>
            </div>
            <div className="relatorio-bloco">
              <h2>Confiança média (prioridade)</h2>
              <p style={{ fontSize: '1.8rem', fontWeight: 600, margin: 0 }}>
                {Math.round(qualidade.confianca_media_prioridade * 100)}%
              </p>
            </div>
          </div>

          <div className="relatorio-bloco">
            <h2>Correções mais recentes</h2>
            {correcoes.length === 0 && <p className="vazio">Nenhuma correção registrada ainda.</p>}
            {correcoes.length > 0 && (
              <table className="tabela">
                <thead>
                  <tr>
                    <th>Chamado</th>
                    <th>Categoria original → corrigida</th>
                    <th>Prioridade original → corrigida</th>
                    <th>Corrigido por</th>
                  </tr>
                </thead>
                <tbody>
                  {correcoes.map((c) => (
                    <tr key={c.id}>
                      <td>{c.solicitacao?.titulo}</td>
                      <td>{c.original_category} → {c.corrected_category}</td>
                      <td>{c.original_priority} → {c.corrected_priority}</td>
                      <td>{c.corretor?.name}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      )}

      {!carregando && aba === 'relatorios' && (
        <div className="relatorios">
          <div className="relatorios-topo">
            <p className="pagina-subtitulo" style={{ margin: 0 }}>
              Os três relatórios abaixo, reunidos numa única planilha.
            </p>
            <button onClick={baixarExcel}>Baixar relatório em Excel</button>
          </div>

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