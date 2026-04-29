import { useState } from 'react';

type WizardProps = {
  onComplete: () => void;
};

export function OnboardingWizard({ onComplete }: WizardProps) {
  const [step, setStep] = useState(1);

  return (
    <div className="wizard-overlay">
      <div className="wizard-content">
        {step === 1 && (
          <>
            <h3>Welcome to PopupPilot!</h3>
            <p>Let's get started by creating your first high-converting popup.</p>
            <button onClick={() => setStep(2)}>Next</button>
          </>
        )}
        {step === 2 && (
          <>
            <h3>Choose a Template</h3>
            <p>Start from scratch or use one of our optimized templates.</p>
            <button onClick={() => setStep(3)}>Next</button>
          </>
        )}
        {step === 3 && (
          <>
            <h3>Set Up Targeting</h3>
            <p>Control exactly who sees your popup and when.</p>
            <button onClick={onComplete}>Finish & Create</button>
          </>
        )}
      </div>
    </div>
  );
}
