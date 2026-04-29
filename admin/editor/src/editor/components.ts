export type PopupComponentType = 'text' | 'image' | 'button' | 'form' | 'countdown' | 'video';

export type PopupComponent = {
  id: string;
  type: PopupComponentType;
  x: number;
  y: number;
  zIndex: number;
  hidden: boolean;
  locked: boolean;
  width?: number;
  height?: number;
  props: Record<string, unknown>;
};

export const COMPONENT_LIBRARY: Array<{ type: PopupComponentType; label: string }> = [
  { type: 'text', label: 'Text' },
  { type: 'image', label: 'Image' },
  { type: 'button', label: 'Button' },
  { type: 'form', label: 'Form' },
  { type: 'countdown', label: 'Countdown' },
  { type: 'video', label: 'Video' }
];

export function createComponent(type: PopupComponentType, id: string): PopupComponent {
  const base: PopupComponent = {
    id,
    type,
    x: 24,
    y: 24,
    zIndex: 1,
    hidden: false,
    locked: false,
    props: {}
  };

  if (type === 'text') {
    return { ...base, width: 220, props: { text: 'Your message here' } };
  }

  if (type === 'image') {
    return {
      ...base,
      width: 220,
      height: 120,
      props: { src: 'https://via.placeholder.com/220x120', alt: 'Image' }
    };
  }

  if (type === 'button') {
    return { ...base, width: 140, props: { label: 'Click me', href: '#' } };
  }

  if (type === 'form') {
    return {
      ...base,
      width: 260,
      props: {
        fields: [
          { name: 'email', label: 'Email', type: 'email', required: true },
          { name: 'name', label: 'Name', type: 'text', required: false }
        ]
      }
    };
  }

  if (type === 'countdown') {
    return {
      ...base,
      width: 220,
      props: { endsAt: new Date(Date.now() + 86400000).toISOString() }
    };
  }

  return {
    ...base,
    width: 260,
    height: 146,
    props: { url: 'https://www.youtube.com/embed/dQw4w9WgXcQ' }
  };
}
