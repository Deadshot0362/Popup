import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { OnboardingWizard } from '../components/onboarding-wizard';

export function PopupListPage() {
  const [showWizard, setShowWizard] = useState(false);

  useEffect(() => {
    const seen = localStorage.getItem('popuppilot_wizard_seen');
    if (!seen) {
      setShowWizard(true);
    }
  }, []);

  return (
    <section>
      {showWizard && (
        <OnboardingWizard
          onComplete={() => {
            setShowWizard(false);
            localStorage.setItem('popuppilot_wizard_seen', '1');
          }}
        />
      )}
      <h2>Popups</h2>
      <p>No popups yet.</p>
      <Link to="/popups/new">Create from blank template</Link>
    </section>
  );
}
