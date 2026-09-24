import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../api/client';

export default function NovoChamado() {
  const navigate = useNavigate();
  const [titulo, setTitulo] = useState('');
  const [descricao, setDescricao] = useState('');
  const [erro, setErro] = useState('');
  const [enviando, setEnviando] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setErro('');
    setEnviando(true);
    try {
      const { data } = await api.post('/solicitacoes', { titulo, descricao });
      navigate(`/chamados/${data.id}`);
    } catch (err) {
      setErro('Não foi possível abrir o chamado. Confira os campos preenchidos.');
    } finally {
      setEnviando(false);
    }
  }

  return (
    <div className="pagina">
      <div className="pagina-cabecalho">
        <h1>Abrir chamado</h1>
      </div>
      <p className="pagina-subtitulo">
        Descreva o problema com suas palavras — a categoria e a prioridade são identificadas automaticamente.
      </p>

      <form className="formulario" onSubmit={handleSubmit}>
        <label>
          Título
          <input value={titulo} onChange={(e) => setTitulo(e.target.value)} required maxLength={255} />
        </label>

        <label>
          Descrição
          <textarea value={descricao} onChange={(e) => setDescricao(e.target.value)} required rows={5} />
        </label>

        {erro && <p className="erro">{erro}</p>}

        <button type="submit" disabled={enviando}>
          {enviando ? 'Enviando...' : 'Abrir chamado'}
        </button>
      </form>
    </div>
  );
}