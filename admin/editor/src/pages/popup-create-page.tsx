import { useMemo, useState } from 'react';
import { DraggableCanvas } from '../components/draggable-canvas';
import { EditorCanvas } from '../components/editor-canvas';
import {
  COMPONENT_LIBRARY,
  createComponent,
  type PopupComponent,
  type PopupComponentType
} from '../editor/components';

type Device = 'desktop' | 'tablet' | 'mobile';
type AnimationPreset = 'none' | 'fade' | 'slide-up' | 'zoom';

type DeviceComponentState = Record<Device, PopupComponent[]>;

type PopupStep = {
  id: string;
  name: string;
  componentsByDevice: DeviceComponentState;
};

type EditorSnapshot = {
  steps: PopupStep[];
  selectedStepId: string;
  selectedId: string | null;
};
type LocalTemplate = { id: string; name: string; steps: PopupStep[] };

const DEVICE_LAYOUT: Record<Device, { width: number; height: number }> = {
  desktop: { width: 600, height: 400 },
  tablet: { width: 500, height: 600 },
  mobile: { width: 320, height: 520 }
};

const MAX_HISTORY = 50;

function createEmptyDeviceState(): DeviceComponentState {
  return { desktop: [], tablet: [], mobile: [] };
}

function cloneDeviceState(state: DeviceComponentState): DeviceComponentState {
  return {
    desktop: state.desktop.map((item) => ({ ...item, props: { ...item.props } })),
    tablet: state.tablet.map((item) => ({ ...item, props: { ...item.props } })),
    mobile: state.mobile.map((item) => ({ ...item, props: { ...item.props } }))
  };
}

function cloneSteps(steps: PopupStep[]): PopupStep[] {
  return steps.map((step) => ({
    ...step,
    componentsByDevice: cloneDeviceState(step.componentsByDevice)
  }));
}

export function PopupCreatePage() {
  const [name, setName] = useState('Untitled Popup');
  const [device, setDevice] = useState<Device>('desktop');
  const [steps, setSteps] = useState<PopupStep[]>([
    { id: 'step-1', name: 'Step 1', componentsByDevice: createEmptyDeviceState() }
  ]);
  const [selectedStepId, setSelectedStepId] = useState('step-1');
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [animation, setAnimation] = useState<AnimationPreset>('none');
  const [historyPast, setHistoryPast] = useState<EditorSnapshot[]>([]);
  const [historyFuture, setHistoryFuture] = useState<EditorSnapshot[]>([]);
  const [templates, setTemplates] = useState<LocalTemplate[]>([]);
  const [importJson, setImportJson] = useState('');
  const [importError, setImportError] = useState<string | null>(null);

  const selectedStep =
    steps.find((step) => step.id === selectedStepId) ?? {
      id: 'step-1',
      name: 'Step 1',
      componentsByDevice: createEmptyDeviceState()
    };
  const components = selectedStep.componentsByDevice[device];

  const captureSnapshot = (): EditorSnapshot => ({
    steps: cloneSteps(steps),
    selectedStepId,
    selectedId
  });

  const pushHistory = () => {
    const snapshot = captureSnapshot();
    setHistoryPast((previous) => {
      const next = [...previous, snapshot];
      return next.length > MAX_HISTORY ? next.slice(next.length - MAX_HISTORY) : next;
    });
    setHistoryFuture([]);
  };

  const restoreSnapshot = (snapshot: EditorSnapshot) => {
    setSteps(cloneSteps(snapshot.steps));
    setSelectedStepId(snapshot.selectedStepId);
    setSelectedId(snapshot.selectedId);
  };

  const undo = () => {
    if (historyPast.length === 0) {
      return;
    }

    const current = captureSnapshot();
    const previous = historyPast[historyPast.length - 1];

    setHistoryPast((items) => items.slice(0, -1));
    setHistoryFuture((items) => [current, ...items]);
    restoreSnapshot(previous);
  };

  const redo = () => {
    if (historyFuture.length === 0) {
      return;
    }

    const current = captureSnapshot();
    const next = historyFuture[0];

    setHistoryFuture((items) => items.slice(1));
    setHistoryPast((items) => {
      const updated = [...items, current];
      return updated.length > MAX_HISTORY ? updated.slice(updated.length - MAX_HISTORY) : updated;
    });
    restoreSnapshot(next);
  };

  const updateCurrentStep = (updater: (step: PopupStep) => PopupStep) => {
    setSteps((previous) =>
      previous.map((step) => (step.id === selectedStep.id ? updater(step) : step))
    );
  };

  const addStep = () => {
    pushHistory();
    const nextIndex = steps.length + 1;
    const id = `step-${String(nextIndex)}`;
    const newStep: PopupStep = { id, name: `Step ${String(nextIndex)}`, componentsByDevice: createEmptyDeviceState() };
    setSteps((previous) => [...previous, newStep]);
    setSelectedStepId(id);
    setSelectedId(null);
  };

  const removeCurrentStep = () => {
    if (steps.length <= 1) {
      return;
    }

    pushHistory();
    const remaining = steps.filter((step) => step.id !== selectedStep.id);
    setSteps(remaining);
    setSelectedStepId(remaining[0]?.id ?? 'step-1');
    setSelectedId(null);
  };

  const addComponent = (type: PopupComponentType) => {
    pushHistory();
    const id = `${type}-${String(Date.now())}`;
    const nextZIndex = components.length + 1;
    updateCurrentStep((step) => ({
      ...step,
      componentsByDevice: {
        ...step.componentsByDevice,
        [device]: [...step.componentsByDevice[device], { ...createComponent(type, id), zIndex: nextZIndex }]
      }
    }));
    setSelectedId(id);
  };

  const moveComponent = (id: string, deltaX: number, deltaY: number) => {
    pushHistory();
    updateCurrentStep((step) => ({
      ...step,
      componentsByDevice: {
        ...step.componentsByDevice,
        [device]: step.componentsByDevice[device].map((component) => {
          if (component.id !== id || component.locked || component.hidden) {
            return component;
          }

          return { ...component, x: component.x + deltaX, y: component.y + deltaY };
        })
      }
    }));
  };

  const selectedComponent = components.find((item) => item.id === selectedId) ?? null;

  const updateSelected = (updater: (component: PopupComponent) => PopupComponent) => {
    if (!selectedId) {
      return;
    }

    pushHistory();
    updateCurrentStep((step) => ({
      ...step,
      componentsByDevice: {
        ...step.componentsByDevice,
        [device]: step.componentsByDevice[device].map((component) =>
          component.id === selectedId ? updater(component) : component
        )
      }
    }));
  };

  const blankDocument = useMemo(
    () => ({
      version: '1.0.0',
      meta: { name },
      animation,
      steps: steps.map((step) => ({
        id: step.id,
        name: step.name,
        layout: {
          width: DEVICE_LAYOUT[device].width,
          height: DEVICE_LAYOUT[device].height,
          device
        },
        components: step.componentsByDevice[device]
      }))
    }),
    [animation, device, name, steps]
  );

  const saveCurrentTemplate = () => {
    const templateName = `Template ${String(templates.length + 1)}`;
    setTemplates((previous) => [
      ...previous,
      { id: `tpl-local-${String(Date.now())}`, name: templateName, steps: cloneSteps(steps) }
    ]);
  };

  const loadTemplate = (template: LocalTemplate) => {
    pushHistory();
    setSteps(cloneSteps(template.steps));
    setSelectedStepId(template.steps[0]?.id ?? 'step-1');
    setSelectedId(null);
  };

  const exportJson = () => {
    const payload = JSON.stringify(blankDocument, null, 2);
    window.navigator.clipboard.writeText(payload).catch(() => undefined);
  };

  const importFromJson = () => {
    setImportError(null);
    try {
      const parsed = JSON.parse(importJson) as {
        steps?: Array<{ id?: string; name?: string; components?: PopupComponent[] }>;
      };
      if (!Array.isArray(parsed.steps) || parsed.steps.length === 0) {
        setImportError('Invalid JSON: at least one step is required.');
        return;
      }

      pushHistory();
      const mapped = parsed.steps.map((step, index) => ({
        id: step.id ?? `step-${String(index + 1)}`,
        name: step.name ?? `Step ${String(index + 1)}`,
        componentsByDevice: {
          desktop: Array.isArray(step.components) ? step.components : [],
          tablet: Array.isArray(step.components) ? step.components : [],
          mobile: Array.isArray(step.components) ? step.components : []
        }
      }));
      setSteps(mapped);
      setSelectedStepId(mapped[0]?.id ?? 'step-1');
      setSelectedId(null);
      setImportJson('');
    } catch {
      setImportError('Invalid JSON format.');
    }
  };

  return (
    <section className="editor-page">
      <h2>Create Popup</h2>
      <label htmlFor="popup-name">Popup Name</label>
      <input
        id="popup-name"
        value={name}
        onChange={(event) => {
          setName(event.target.value);
        }}
      />

      <div className="step-row">
        {steps.map((step) => (
          <button
            key={step.id}
            type="button"
            className={selectedStepId === step.id ? 'active' : ''}
            onClick={() => {
              setSelectedStepId(step.id);
              setSelectedId(null);
            }}
          >
            {step.name}
          </button>
        ))}
        <button type="button" onClick={addStep}>+ Step</button>
        <button type="button" onClick={removeCurrentStep} disabled={steps.length <= 1}>Remove Step</button>
      </div>

      <div className="device-switcher">
        {(['desktop', 'tablet', 'mobile'] as const).map((mode) => (
          <button
            key={mode}
            type="button"
            className={mode === device ? 'active' : ''}
            onClick={() => {
              setDevice(mode);
            }}
          >
            {mode}
          </button>
        ))}
      </div>

      <div className="library-row">
        <button
          type="button"
          onClick={() => {
            undo();
          }}
          disabled={historyPast.length === 0}
        >
          Undo
        </button>
        <button
          type="button"
          onClick={() => {
            redo();
          }}
          disabled={historyFuture.length === 0}
        >
          Redo
        </button>
        {COMPONENT_LIBRARY.map((entry) => (
          <button
            key={entry.type}
            type="button"
            onClick={() => {
              addComponent(entry.type);
            }}
          >
            Add {entry.label}
          </button>
        ))}
        <button
          type="button"
          onClick={() => {
            saveCurrentTemplate();
          }}
        >
          Save Template
        </button>
        <button type="button" onClick={exportJson}>
          Export JSON
        </button>
      </div>

      <div className="editor-layout">
        <EditorCanvas device={device} width={DEVICE_LAYOUT[device].width} height={DEVICE_LAYOUT[device].height}>
          <DraggableCanvas
            components={components}
            selectedId={selectedId}
            onDragEnd={moveComponent}
            onSelect={(id) => {
              setSelectedId(id);
            }}
            animation={animation}
          />
        </EditorCanvas>
        <aside className="side-panel">
          <h4>Properties</h4>
          <label htmlFor="anim">Animation</label>
          <select
            id="anim"
            value={animation}
            onChange={(event) => {
              setAnimation(event.target.value as AnimationPreset);
            }}
          >
            <option value="none">None</option>
            <option value="fade">Fade</option>
            <option value="slide-up">Slide Up</option>
            <option value="zoom">Zoom</option>
          </select>
          {selectedComponent ? (
            <>
              <label htmlFor="z-index">Z-Index</label>
              <input
                id="z-index"
                type="number"
                value={selectedComponent.zIndex}
                onChange={(event) => {
                  updateSelected((component) => ({
                    ...component,
                    zIndex: Number(event.target.value) || component.zIndex
                  }));
                }}
              />
              <label htmlFor="x-pos">X</label>
              <input
                id="x-pos"
                type="number"
                value={selectedComponent.x}
                onChange={(event) => {
                  updateSelected((component) => ({
                    ...component,
                    x: Number(event.target.value) || component.x
                  }));
                }}
              />
              <label htmlFor="y-pos">Y</label>
              <input
                id="y-pos"
                type="number"
                value={selectedComponent.y}
                onChange={(event) => {
                  updateSelected((component) => ({
                    ...component,
                    y: Number(event.target.value) || component.y
                  }));
                }}
              />
              <button
                type="button"
                onClick={() => {
                  updateSelected((component) => ({ ...component, locked: !component.locked }));
                }}
              >
                {selectedComponent.locked ? 'Unlock' : 'Lock'}
              </button>
              <button
                type="button"
                onClick={() => {
                  updateSelected((component) => ({ ...component, hidden: !component.hidden }));
                }}
              >
                {selectedComponent.hidden ? 'Show' : 'Hide'}
              </button>
            </>
          ) : (
            <p>Select a component to edit properties.</p>
          )}
          <h4>Layers</h4>
          <div className="layer-list">
            {[...components]
              .sort((a, b) => b.zIndex - a.zIndex)
              .map((component) => (
                <button
                  key={component.id}
                  type="button"
                  className={selectedId === component.id ? 'layer-item active' : 'layer-item'}
                  onClick={() => {
                    setSelectedId(component.id);
                  }}
                >
                  <span>{component.type}</span>
                  <span>z:{component.zIndex}</span>
                </button>
              ))}
          </div>
          <h4>Templates</h4>
          <div className="layer-list">
            {templates.length === 0 ? (
              <p>No templates saved yet.</p>
            ) : (
              templates.map((template) => (
                <button
                  key={template.id}
                  type="button"
                  className="layer-item"
                  onClick={() => {
                    loadTemplate(template);
                  }}
                >
                  <span>{template.name}</span>
                  <span>{template.steps.length} step(s)</span>
                </button>
              ))
            )}
          </div>
          <h4>Import JSON</h4>
          <textarea
            value={importJson}
            onChange={(event) => {
              setImportJson(event.target.value);
            }}
            rows={6}
            placeholder="Paste popup JSON here"
          />
          <button
            type="button"
            onClick={() => {
              importFromJson();
            }}
          >
            Import JSON
          </button>
          {importError ? <p>{importError}</p> : null}
        </aside>
      </div>
      <pre>{JSON.stringify(blankDocument, null, 2)}</pre>
    </section>
  );
}
