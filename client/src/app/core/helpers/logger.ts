type LoggerType = 'log' | 'info' | 'warn' | 'error';

const LoggerModulesConfig = {
  ConfigService: {
    background: '#d10000',
    foreground: 'white',
  },
  StorageService: {
    background: '#630000',
    foreground: 'white',
  },
  AuthService: {
    background: '#037ffc',
    foreground: 'white',
  },
  Authentication: {
    background: '#030bfc',
    foreground: 'white',
  },
  Recipes: {
    background: '#fc9d03',
    foreground: 'white',
  },
};

export class Logger {
  constructor(private moduleName: keyof typeof LoggerModulesConfig) {}

  write(type: LoggerType, ...message: any[]) {
    console[type](
      `%c ${this.moduleName} `,
      `color: ${this.foreground}; background: ${this.background}; border-radius: 2px`,
      ...message
    );
  }

  log(...message: any[]) {
    this.write('log', ...message);
  }
  info(...message: any[]) {
    this.write('info', ...message);
  }
  warn(...message: any[]) {
    this.write('warn', ...message);
  }
  error(...message: any[]) {
    this.write('error', ...message);
  }

  private get background() {
    return LoggerModulesConfig[this.moduleName].background;
  }
  private get foreground() {
    return LoggerModulesConfig[this.moduleName].foreground;
  }
}
