import { useState } from 'react';
import Slider from 'react-slick';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import { Calendar, Figma, Zap, BarChart2, Users, MessageSquare, GitBranch, Bell, Heart, Shirt } from 'lucide-react';

interface Tool {
  id: number;
  name: string;
  description: string;
  icon: React.ReactNode;
  color: string;
}

const tools: Tool[] = [
  {
    id: 1,
    name: 'Monday',
    description: 'Project management tool',
    icon: <div className="text-red-500"><BarChart2 size={32} /></div>,
    color: '#ff3d57'
  },
  {
    id: 2,
    name: 'Notion',
    description: 'Workspace voor notities',
    icon: <div className="text-gray-800"><MessageSquare size={32} /></div>,
    color: '#000000'
  },
  {
    id: 3,
    name: 'Figma',
    description: 'Design tool voor UI/UX',
    icon: <div className="text-purple-500"><Figma size={32} /></div>,
    color: '#a259ff'
  },
  {
    id: 4,
    name: 'Calendly',
    description: 'Kalender planning tool',
    icon: <div className="text-blue-500"><Calendar size={32} /></div>,
    color: '#006bff'
  },
  {
    id: 5,
    name: 'Zapier',
    description: 'Automatiseer workflows',
    icon: <div className="text-orange-500"><Zap size={32} /></div>,
    color: '#ff4a00'
  },
  {
    id: 6,
    name: 'Analytics',
    description: 'Data analyse platform',
    icon: <div className="text-yellow-500"><BarChart2 size={32} /></div>,
    color: '#ffc107'
  },
  {
    id: 7,
    name: 'Slack',
    description: 'Communicatie platform',
    icon: <div className="text-pink-500"><MessageSquare size={32} /></div>,
    color: '#e01e5a'
  },
  {
    id: 8,
    name: 'GitHub',
    description: 'Code repository tool',
    icon: <div className="text-gray-700"><GitBranch size={32} /></div>,
    color: '#24292e'
  },
  {
    id: 9,
    name: 'Mailchimp',
    description: 'Email marketing tool',
    icon: <div className="text-yellow-600"><Bell size={32} /></div>,
    color: '#ffe01b'
  },
  {
    id: 10,
    name: 'Spotify',
    description: 'Muziek streaming service',
    icon: <div className="text-green-500"><Heart size={32} /></div>,
    color: '#1db954'
  }
];

export function ToolSlider() {
  const [hoveredTool, setHoveredTool] = useState<number | null>(null);

  const settings = {
    dots: false,
    infinite: true,
    speed: 500,
    slidesToShow: 8,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 3000,
    pauseOnHover: true,
    responsive: [
      {
        breakpoint: 1400,
        settings: {
          slidesToShow: 6,
        }
      },
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 5,
        }
      },
      {
        breakpoint: 768,
        settings: {
          slidesToShow: 4,
        }
      },
      {
        breakpoint: 640,
        settings: {
          slidesToShow: 3,
        }
      }
    ]
  };

  return (
    <div className="w-full max-w-7xl mx-auto px-8 py-24">
      <style>{`
        .slick-slider {
          overflow: visible !important;
        }
        .slick-list {
          overflow: visible !important;
        }
        .slick-track {
          display: flex !important;
          align-items: center !important;
        }
      `}</style>
      <Slider {...settings}>
        {tools.map((tool) => (
          <div key={tool.id} className="px-3">
            <div 
              className="relative flex items-center justify-center py-4"
              onMouseEnter={() => setHoveredTool(tool.id)}
              onMouseLeave={() => setHoveredTool(null)}
            >
              <div className="bg-gray-100 rounded-2xl w-16 h-16 flex items-center justify-center hover:bg-gray-200 transition-all duration-200 cursor-pointer hover:scale-110">
                {tool.icon}
              </div>
              
              {/* Tooltip */}
              {hoveredTool === tool.id && (
                <div 
                  className="absolute pointer-events-none"
                  style={{
                    left: '50%',
                    bottom: 'calc(100% + 8px)',
                    transform: 'translateX(-50%)',
                    zIndex: 9999
                  }}
                >
                  <div className="bg-gray-900 text-white px-5 py-3 rounded-xl shadow-2xl min-w-[220px] text-center border border-gray-700">
                    <div className="font-semibold text-sm mb-1.5">{tool.name}</div>
                    <div className="text-xs text-gray-300 leading-relaxed">{tool.description}</div>
                  </div>
                  {/* Arrow */}
                  <div className="absolute left-1/2 -bottom-1 transform -translate-x-1/2 w-3 h-3 bg-gray-900 rotate-45 border-r border-b border-gray-700"></div>
                </div>
              )}
            </div>
          </div>
        ))}
      </Slider>
    </div>
  );
}