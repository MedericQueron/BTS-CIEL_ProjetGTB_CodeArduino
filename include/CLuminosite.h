#ifndef CLUMINOSITE_H
#define CLUMINOSITE_H

#include "arduino.h"
#include "CCapteurArduino.h"

class CLuminosite : public CCapteurArduino
{

public:
    CLuminosite(int id, string pin);
    ~CLuminosite();

    void initialiser() override;
    bool getValue();
    float lireLuminosite() const;
};
#endif // 