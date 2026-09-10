import { STATUS_LABELS, STATUS_TONE } from '../constants';

export default function StatusBadge({ status }) {
  const tone = STATUS_TONE[status] || 'neutro';
  return <span className={`badge badge-${tone}`}>{STATUS_LABELS[status] || status}</span>;
}
