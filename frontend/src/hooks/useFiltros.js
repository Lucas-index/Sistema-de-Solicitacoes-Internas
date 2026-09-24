import { useState, useMemo } from 'react';

export function useFiltros(chamados) {
  const [filtros, setFiltros] = useState({ texto: '', status: '', prioridade: '' });

  const chamadosFiltrados = useMemo(() => {
    return chamados.filter((c) => {
      const texto = filtros.texto.toLowerCase();
      const bateTexto =
        !texto ||
        c.titulo?.toLowerCase().includes(texto) ||
        c.descricao?.toLowerCase().includes(texto);
      const bateStatus = !filtros.status || c.status === filtros.status;
      const batePrioridade = !filtros.prioridade || c.prioridade === filtros.prioridade;
      return bateTexto && bateStatus && batePrioridade;
    });
  }, [chamados, filtros]);

  return { filtros, setFiltros, chamadosFiltrados };
}