import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import Layout from './components/Layout';
import Login from './pages/Login';
import MeusChamados from './pages/MeusChamados';
import NovoChamado from './pages/NovoChamado';
import ChamadoDetalhe from './pages/ChamadoDetalhe';
import PainelExecutor from './pages/PainelExecutor';
import PainelAdmin from './pages/PainelAdmin';

function Privado({ children }) {
  const { user } = useAuth();
  if (!user) return <Navigate to="/login" replace />;
  return <Layout>{children}</Layout>;
}

function Home() {
  const { user } = useAuth();
  if (!user) return <Navigate to="/login" replace />;
  if (user.papel === 'aprovador' || user.papel === 'admin') {
    return <Navigate to="/painel" replace />;
  }
  if (user.papel === 'executor') {
    return <Navigate to="/fila-execucao" replace />;
  }
  return <Navigate to="/meus-chamados" replace />;
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/" element={<Home />} />
          <Route path="/meus-chamados" element={<Privado><MeusChamados /></Privado>} />
          <Route path="/novo-chamado" element={<Privado><NovoChamado /></Privado>} />
          <Route path="/chamados/:id" element={<Privado><ChamadoDetalhe /></Privado>} />
          <Route path="/fila-execucao" element={<Privado><PainelExecutor /></Privado>} />
          <Route path="/painel" element={<Privado><PainelAdmin /></Privado>} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
