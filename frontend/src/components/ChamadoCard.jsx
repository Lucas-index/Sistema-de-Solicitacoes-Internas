import { Link } from 'react-router-dom';
import StatusBadge from './StatusBadge';
import { PRIORIDADE_LABELS } from '../constants';

export default function ChamadoCard({ chamado }) {
  return (
    <Link to={`/chamados/${chamado.id}`} className="chamado-card">
      <div className="chamado-card-topo">
        <p className="chamado-titulo">{chamado.titulo}</p>
        <StatusBadge status={chamado.status} />
      </div>
      <p className="chamado-descricao">{chamado.descricao}</p>
      <div className="chamado-card-rodape">
        <span>{chamado.categoria?.nome}</span>
        <span>Prioridade: {PRIORIDADE_LABELS[chamado.prioridade] || chamado.prioridade}</span>
      </div>
    </Link>
  );
}
