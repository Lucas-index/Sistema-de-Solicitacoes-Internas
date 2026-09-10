import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../api/client';

export default function NovoChamado() {
  const navigate = useNavigate();
  const [categorias, setCategorias] = useState([]);
  const [categoriaId, setCategoriaId] = useState('');
  const [titulo, setTitulo] = useState('');
  const [descricao, setDescricao] = useState('');
  const [prioridade, setPrioridade] = useState('media');
  const [erro, setErro] = useState('');
  const [enviando, setEnviando] = useState(false);

  useEffect(() => {
    api.get('/categorias').then(({ data }) => setCategorias(data));
  }, []);

  async function handleSubmit(e) {
    e.preventDefault();
    setErro('');
    setEnviando(true);
    try {
      const { data } = await api.post('/solicitacoes', {
        categoria_id: Number(categoriaId),
        titulo,
        descricao,
        prioridade,
      });
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

      <form className="formulario" onSubmit={handleSubmit}>
        <label>
          Categoria
          <select value={categoriaId} onChange={(e) => setCategoriaId(e.target.value)} required>
            <option value="" disabled>Selecione uma categoria</option>
            {categorias.map((c) => (
              <option key={c.id} value={c.id}>{c.nome}</option>
            ))}
          </select>
        </label>

        <label>
          Título
          <input value={titulo} onChange={(e) => setTitulo(e.target.value)} required maxLength={255} />
        </label>

        <label>
          Descrição
          <textarea value={descricao} onChange={(e) => setDescricao(e.target.value)} required rows={5} />
        </label>

        <label>
          Prioridade
          <select value={prioridade} onChange={(e) => setPrioridade(e.target.value)}>
            <option value="baixa">Baixa</option>
            <option value="media">Média</option>
            <option value="alta">Alta</option>
          </select>
        </label>

        {erro && <p className="erro">{erro}</p>}

        <button type="submit" disabled={enviando}>
          {enviando ? 'Enviando...' : 'Abrir chamado'}
        </button>
      </form>
    </div>
  );
}
