import type { ReactNode } from 'react';

type EditorCanvasProps = {
  device: 'desktop' | 'tablet' | 'mobile';
  width: number;
  height: number;
  children?: ReactNode;
};

const deviceClassMap: Record<EditorCanvasProps['device'], string> = {
  desktop: 'canvas-desktop',
  tablet: 'canvas-tablet',
  mobile: 'canvas-mobile'
};

export function EditorCanvas({ device, width, height, children }: EditorCanvasProps) {
  return (
    <section className="editor-canvas-wrap" aria-label="Popup canvas">
      <header className="editor-canvas-header">
        <strong>Canvas</strong>
        <span>{device}</span>
      </header>
      <div className="editor-canvas-stage">
        <div
          className={`editor-canvas ${deviceClassMap[device]}`}
          style={{ width: `${String(width)}px`, height: `${String(height)}px` }}
        >
          {children}
        </div>
      </div>
    </section>
  );
}
