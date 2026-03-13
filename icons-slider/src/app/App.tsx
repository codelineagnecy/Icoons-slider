import { ToolSlider } from './components/ToolSlider';

export default function App() {
  return (
    <div className="size-full flex items-center justify-center bg-white">
      <div className="w-full">
        <div className="text-center mb-12">
          <h1 className="text-3xl mb-2">Tool Integraties</h1>
          <p className="text-gray-600">Hover over de iconen voor meer informatie</p>
        </div>
        <ToolSlider />
      </div>
    </div>
  );
}
