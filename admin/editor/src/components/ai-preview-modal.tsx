import { useState } from 'react';

type AIPreviewProps = {
  onApply: (doc: any) => void;
  onCancel: () => void;
  proposedDoc: any;
};

export function AIPreviewModal({ onApply, onCancel, proposedDoc }: AIPreviewProps) {
  return (
    <div className="ai-modal-overlay">
      <div className="ai-modal-content">
        <h3>AI Suggestion</h3>
        <p>Preview the changes before applying them to your popup.</p>
        <div className="ai-diff-preview">
          <pre>{JSON.stringify(proposedDoc, null, 2)}</pre>
        </div>
        <div className="modal-actions">
          <button type="button" onClick={() => onApply(proposedDoc)}>Apply Changes</button>
          <button type="button" onClick={onCancel} className="secondary">Discard</button>
        </div>
      </div>
    </div>
  );
}
