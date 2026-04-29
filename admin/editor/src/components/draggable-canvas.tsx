import { DndContext, useDraggable, type DragEndEvent } from '@dnd-kit/core';
import type { CSSProperties } from 'react';
import type { PopupComponent } from '../editor/components';

type DraggableCanvasProps = {
  components: PopupComponent[];
  selectedId: string | null;
  animation: 'none' | 'fade' | 'slide-up' | 'zoom';
  onDragEnd: (id: string, deltaX: number, deltaY: number) => void;
  onSelect: (id: string) => void;
};

function RenderComponent({ component }: { component: PopupComponent }) {
  const getPropString = (key: string, fallback: string) => {
    const value = component.props[key];
    return typeof value === 'string' ? value : fallback;
  };

  if (component.type === 'text') {
    return <p>{getPropString('text', '')}</p>;
  }

  if (component.type === 'image') {
    return (
      <img
        src={getPropString('src', '')}
        alt={getPropString('alt', '')}
        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
      />
    );
  }

  if (component.type === 'button') {
    return <button type="button">{getPropString('label', 'Button')}</button>;
  }

  if (component.type === 'form') {
    return (
      <form>
        <input placeholder="Email" type="email" />
        <button type="submit">Submit</button>
      </form>
    );
  }

  if (component.type === 'countdown') {
    return <div>Ends: {getPropString('endsAt', '')}</div>;
  }

  return <iframe title={component.id} src={getPropString('url', '')} width="100%" height="100%" />;
}

function DraggableItem({
  component,
  selected,
  animation,
  onSelect
}: {
  component: PopupComponent;
  selected: boolean;
  animation: 'none' | 'fade' | 'slide-up' | 'zoom';
  onSelect: (id: string) => void;
}) {
  const { attributes, listeners, setNodeRef, transform } = useDraggable({
    id: component.id,
    disabled: component.locked || component.hidden
  });

  const style: CSSProperties = {
    position: 'absolute',
    left: component.x,
    top: component.y,
    width: component.width,
    height: component.height,
    zIndex: component.zIndex,
    opacity: component.hidden ? 0.25 : 1,
    borderColor: selected ? '#3a8dde' : '#cad4dc',
    animation:
      animation === 'none'
        ? undefined
        : animation === 'fade'
          ? 'ppFadeIn 0.4s ease'
          : animation === 'slide-up'
            ? 'ppSlideUp 0.4s ease'
            : 'ppZoomIn 0.35s ease',
    transform: transform
      ? `translate3d(${String(transform.x)}px, ${String(transform.y)}px, 0)`
      : undefined
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className="canvas-item"
      onClick={() => {
        onSelect(component.id);
      }}
      {...listeners}
      {...attributes}
    >
      <RenderComponent component={component} />
    </div>
  );
}

export function DraggableCanvas({
  components,
  selectedId,
  animation,
  onDragEnd,
  onSelect
}: DraggableCanvasProps) {
  const handleDragEnd = (event: DragEndEvent) => {
    const id = String(event.active.id);
    onDragEnd(id, event.delta.x, event.delta.y);
  };

  return (
    <DndContext onDragEnd={handleDragEnd}>
      {components.map((component) => (
        <DraggableItem
          key={component.id}
          component={component}
          selected={selectedId === component.id}
          animation={animation}
          onSelect={onSelect}
        />
      ))}
    </DndContext>
  );
}
