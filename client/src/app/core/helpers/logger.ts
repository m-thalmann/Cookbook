type LoggerType = 'log' | 'info' | 'warn' | 'error';

const LoggerModulesConfig = {
  ConfigService: {
    background: 'red',
    foreground: 'white',
  },
  StorageService: {
    background: 'darkred',
    foreground: 'white',
  },
  AuthService: {
    background: 'turquoise',
    foreground: 'white',
  },
  ErrorHandlerService: {
    background: 'orangered',
    foreground: 'white',
  },
  Authentication: {
    background: 'blue',
    foreground: 'white',
  },
  Recipes: {
    background: 'orange',
    foreground: 'white',
  },
  Cookbooks: {
    background: 'coral',
    foreground: 'white',
  },
  Settings: {
    background: 'purple',
    foreground: 'white',
  },
  Admin: {
    background: 'darkgreen',
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
