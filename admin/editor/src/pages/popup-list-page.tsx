import { Link } from 'react-router-dom';

export function PopupListPage() {
  return (
    <section>
      <h2>Popups</h2>
      <p>No popups yet.</p>
      <Link to="/popups/new">Create from blank template</Link>
    </section>
  );
}
