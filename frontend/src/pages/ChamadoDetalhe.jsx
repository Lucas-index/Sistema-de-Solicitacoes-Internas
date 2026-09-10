import { useEffect, useState, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import { api } from '../api/client';
import { useAuth } from '../context/AuthContext';
import StatusBadge from '../components/StatusBadge';
import { PRIORIDADE_LABELS } from '../constants';

const STORAGE_URL = import.meta.env.VITE_STORAGE_URL || 'http://127.0.0.1:8000/storage';

export default function ChamadoDetalhe() {
  const { id } = useParams();
  const { user } = useAuth();
  const [chamado, setChamado] = useState(null);
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState('');
  const [acaoEmAndamento, setAcaoEmAndamento] = useState(false);
  const [motivoRejeicao, setMotivoRejeicao] = useState('');
  const [mostrarRejeicao, setMostrarRejeicao] = useState(false);
  const [novoComentario, setNovoComentario] = useState('');
  const [arquivo, setArquivo] = useState(null);

  const carregar = useCallback(() => {
    setCarregando(true);
    api
      .get(`/solicitacoes/${id}`)
      .then(({ data }) => setChamado(data))
      .catch(() => setErro('Não foi possível carregar este chamado.'))
      .finally(() => setCarregando(false));
  }, [id]);

  useEffect(() => {
    carregar();
  }, [carregar]);

  async function executarAcao(acao, body) {
    setAcaoEmAndamento(true);
    setErro('');
    try {
      await api.post(`/solicitacoes/${id}/${acao}`, body);
      carregar();
      setMostrarRejeicao(false);
      setMotivoRejeicao('');
    } catch (err) {
      setErro('Não foi possível concluir essa ação.');
    } finally {
      setAcaoEmAndamento(false);
    }
  }

  async function enviarComentario(e) {
    e.preventDefault();
    if (!novoComentario.trim()) return;
    await api.post(`/solicitacoes/${id}/comentarios`, { texto: novoComentario });
    setNovoComentario('');
    carregar();
  }

  async function enviarAnexo(e) {
    e.preventDefault();
    if (!arquivo) return;
    const formData = new FormData();
    formData.append('arquivo', arquivo);
    await api.post(`/solicitacoes/${id}/anexos`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    setArquivo(null);
    carregar();
  }

  if (carregando) {
    return (
      <div className="pagina">
        <p>Carregando...</p>
      </div>
    );
  }

  if (erro && !chamado) {
    return (
      <div className="pagina">
        <p className="erro">{erro}</p>
      </div>
    );
  }

  if (!chamado) return null;

  const ehDono = chamado.usuario_id === user.id;
  const ehAprovadorDesteChamado = chamado.aprovador_id === user.id;

  const podeAprovarOuRejeitar =
    chamado.status === 'pendente_aprovacao' && (ehAprovadorDesteChamado || user.papel === 'admin');

  const podeExecutar =
    chamado.status === 'aprovada' && (user.papel === 'executor' || user.papel === 'admin');

  const podeConcluir =
    chamado.status === 'em_execucao' && (chamado.executor_id === user.id || user.papel === 'admin');

  const podeCancelar = ['criada', 'pendente_aprovacao'].includes(chamado.status) && ehDono;

  const podeFechar = chamado.status === 'concluida' && ehDono;

  return (
    <div className="pagina">
      <div className="pagina-cabecalho">
        <div>
          <h1>{chamado.titulo}</h1>
          <p className="chamado-meta">
            Aberto por {chamado.usuario?.name} · Categoria {chamado.categoria?.nome} · Prioridade{' '}
            {PRIORIDADE_LABELS[chamado.prioridade]}
          </p>
        </div>
        <StatusBadge status={chamado.status} />
      </div>

      <p className="chamado-descricao-completa">{chamado.descricao}</p>

      {erro && <p className="erro">{erro}</p>}

      <div className="acoes-chamado">
        {podeAprovarOuRejeitar && (
          <>
            <button disabled={acaoEmAndamento} onClick={() => executarAcao('aprovar')}>
              Aprovar
            </button>
            <button
              disabled={acaoEmAndamento}
              className="botao-secundario"
              onClick={() => setMostrarRejeicao((v) => !v)}
            >
              Rejeitar
            </button>
          </>
        )}

        {podeExecutar && (
          <button disabled={acaoEmAndamento} onClick={() => executarAcao('executar')}>
            Assumir e executar
          </button>
        )}

        {podeConcluir && (
          <button disabled={acaoEmAndamento} onClick={() => executarAcao('concluir')}>
            Marcar como concluído
          </button>
        )}

        {podeFechar && (
          <button disabled={acaoEmAndamento} onClick={() => executarAcao('fechar')}>
            Confirmar e fechar
          </button>
        )}

        {podeCancelar && (
          <button disabled={acaoEmAndamento} className="botao-perigo" onClick={() => executarAcao('cancelar')}>
            Cancelar chamado
          </button>
        )}
      </div>

      {mostrarRejeicao && (
        <form
          className="formulario-rejeicao"
          onSubmit={(e) => {
            e.preventDefault();
            executarAcao('rejeitar', { motivo: motivoRejeicao });
          }}
        >
          <label>
            Motivo da rejeição
            <textarea
              value={motivoRejeicao}
              onChange={(e) => setMotivoRejeicao(e.target.value)}
              required
              rows={3}
            />
          </label>
          <button type="submit" disabled={acaoEmAndamento}>
            Confirmar rejeição
          </button>
        </form>
      )}

      <section className="secao">
        <h2>Linha do tempo</h2>
        <ul className="timeline">
          {chamado.historico?.map((h) => (
            <li key={h.id}>
              <StatusBadge status={h.status_novo} />
              {h.observacao && <p className="timeline-observacao">{h.observacao}</p>}
            </li>
          ))}
        </ul>
      </section>

      <section className="secao">
        <h2>Anexos</h2>
        <ul className="lista-anexos">
          {chamado.anexos?.length === 0 && <p className="vazio">Nenhum anexo ainda.</p>}
          {chamado.anexos?.map((a) => (
            <li key={a.id}>
              <a href={`${STORAGE_URL}/${a.caminho_arquivo}`} target="_blank" rel="noreferrer">
                {a.caminho_arquivo.split('/').pop()}
              </a>
            </li>
          ))}
        </ul>
        <form className="formulario-inline" onSubmit={enviarAnexo}>
          <input type="file" onChange={(e) => setArquivo(e.target.files[0])} />
          <button type="submit" disabled={!arquivo}>Anexar</button>
        </form>
      </section>

      <section className="secao">
        <h2>Comentários</h2>
        <ul className="lista-comentarios">
          {chamado.comentarios?.length === 0 && <p className="vazio">Nenhum comentário ainda.</p>}
          {chamado.comentarios?.map((c) => (
            <li key={c.id}>
              <p className="comentario-autor">{c.usuario?.name}</p>
              <p>{c.texto}</p>
            </li>
          ))}
        </ul>
        <form className="formulario-inline" onSubmit={enviarComentario}>
          <input
            value={novoComentario}
            onChange={(e) => setNovoComentario(e.target.value)}
            placeholder="Escreva um comentário"
          />
          <button type="submit">Enviar</button>
        </form>
      </section>
    </div>
  );
}
