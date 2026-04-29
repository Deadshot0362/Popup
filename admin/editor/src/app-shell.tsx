import { NavLink, Route, Routes } from 'react-router-dom';
import { PopupCreatePage } from './pages/popup-create-page';
import { PopupListPage } from './pages/popup-list-page';

function Placeholder({ title }: { title: string }) {
  return <section><h2>{title}</h2><p>Coming soon.</p></section>;
}

export function AppShell() {
  return (
    <div className="app-shell">
      <aside className="sidebar">
        <h1>PopupPilot</h1>
        <nav>
          <NavLink to="/popups">Popups</NavLink>
          <NavLink to="/popups/new">Create Popup</NavLink>
          <NavLink to="/campaigns">Campaigns</NavLink>
          <NavLink to="/integrations">Integrations</NavLink>
          <NavLink to="/settings">Settings</NavLink>
        </nav>
      </aside>
      <main className="content">
        <Routes>
          <Route path="/popups" element={<PopupListPage />} />
          <Route path="/popups/new" element={<PopupCreatePage />} />
          <Route path="/campaigns" element={<Placeholder title="Campaigns" />} />
          <Route path="/integrations" element={<Placeholder title="Integrations" />} />
          <Route path="/settings" element={<Placeholder title="Settings" />} />
          <Route path="*" element={<PopupListPage />} />
        </Routes>
      </main>
    </div>
  );
}
