import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import NotificationsBell from './NotificationsBell';
import { PAPEL_LABELS } from '../constants';

export default function Layout({ children }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  function handleLogout() {
    logout();
    navigate('/login');
  }

  const isAdminOuAprovador = user.papel === 'admin' || user.papel === 'aprovador';
  const isExecutor = user.papel === 'executor';

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="sidebar-topo">
          <p className="sidebar-empresa">Empresa</p>
          <p className="sidebar-produto">Solicitações Internas</p>
        </div>

        <nav className="sidebar-nav">
          <Link className={location.pathname === '/meus-chamados' ? 'ativo' : ''} to="/meus-chamados">
            Meus chamados
          </Link>
          <Link className={location.pathname === '/novo-chamado' ? 'ativo' : ''} to="/novo-chamado">
            Abrir chamado
          </Link>

          {isExecutor && (
            <Link className={location.pathname === '/fila-execucao' ? 'ativo' : ''} to="/fila-execucao">
              Fila de execução
            </Link>
          )}

          {isAdminOuAprovador && (
            <Link className={location.pathname === '/painel' ? 'ativo' : ''} to="/painel">
              Painel administrativo
            </Link>
          )}
        </nav>

        <div className="sidebar-rodape">
          <div className="sidebar-usuario">
            <p className="usuario-nome">{user.name}</p>
            <p className="usuario-papel">{PAPEL_LABELS[user.papel] || user.papel}</p>
          </div>
          <button className="botao-sair" onClick={handleLogout}>Sair</button>
        </div>
      </aside>

      <div className="app-corpo">
        <header className="topbar">
          <NotificationsBell />
        </header>
        <main className="conteudo">{children}</main>
      </div>
    </div>
  );
}
