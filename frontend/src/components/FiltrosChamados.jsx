import { PRIORIDADE_LABELS, STATUS_LABELS } from '../constants';

export default function FiltrosChamados({ filtros, onChange }) {
  return (
    <div className="filtros-barra">
      <input
        className="filtros-busca"
        type="text"
        placeholder="Buscar por título ou descrição..."
        value={filtros.texto}
        onChange={(e) => onChange({ ...filtros, texto: e.target.value })}
      />

      <select
        value={filtros.status}
        onChange={(e) => onChange({ ...filtros, status: e.target.value })}
      >
        <option value="">Todos os status</option>
        {Object.entries(STATUS_LABELS).map(([valor, label]) => (
          <option key={valor} value={valor}>{label}</option>
        ))}
      </select>

      <select
        value={filtros.prioridade}
        onChange={(e) => onChange({ ...filtros, prioridade: e.target.value })}
      >
        <option value="">Todas as prioridades</option>
        {Object.entries(PRIORIDADE_LABELS).map(([valor, label]) => (
          <option key={valor} value={valor}>{label}</option>
        ))}
      </select>

      {(filtros.texto || filtros.status || filtros.prioridade) && (
        <button
          className="botao-secundario"
          onClick={() => onChange({ texto: '', status: '', prioridade: '' })}
        >
          Limpar filtros
        </button>
      )}
    </div>
  );
}